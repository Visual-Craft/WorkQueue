<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue;

use VisualCraft\WorkQueue\QueueProcessor\QueueProcessorLimits;
use VisualCraft\WorkQueue\QueueProcessor\QueueProcessorStats;
use VisualCraft\WorkQueue\QueueProcessor\RetryDelayProviderInterface;
use VisualCraft\WorkQueue\Worker\AdvancedWorkerInterface;
use VisualCraft\WorkQueue\Worker\JobMetadata;
use VisualCraft\WorkQueue\Worker\RetriableWorkerInterface;
use VisualCraft\WorkQueue\Worker\WorkerInterface;

class QueueProcessor
{
    public const STATE_INITIAL = 0;
    public const STATE_PROCESSING = 1;
    public const STATE_FINISHED = 2;

    private QueueManager $queueManager;

    private WorkerInterface $worker;

    private ?RetryDelayProviderInterface $retryDelayProvider;

    private ?QueueProcessorLimits $limits;

    private int $processStartTime;

    private int $state;

    private int $totalJobsCount;

    private int $successfulJobsCount;

    public function __construct(
        QueueManager $queueManager,
        WorkerInterface $worker,
        ?RetryDelayProviderInterface $retryDelayProvider = null,
        ?QueueProcessorLimits $limits = null
    ) {
        $this->queueManager = $queueManager;
        $this->worker = $worker;
        $this->retryDelayProvider = $retryDelayProvider;
        $this->limits = $limits;
        $this->reset();
    }

    public function getStats(): QueueProcessorStats
    {
        return new QueueProcessorStats($this->totalJobsCount, $this->successfulJobsCount);
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function reset(): void
    {
        $this->state = self::STATE_INITIAL;
        $this->totalJobsCount = 0;
        $this->successfulJobsCount = 0;
    }

    public function process(int $reserveTimeout = 300): bool
    {
        $logger = $this->queueManager->getLogger();

        if ($this->state === self::STATE_FINISHED) {
            throw new \LogicException("Unable to process the queue, the processor is in 'finished' state, call 'reset' method first.");
        }

        if ($this->state === self::STATE_INITIAL) {
            $this->state = self::STATE_PROCESSING;
            $logger->log('info', 'Start processing of the work queue');
            $this->processStartTime = time();
        }

        $timeLeft = $this->checkLimitsAndGetTimeLeft($reserveTimeout);

        if ($timeLeft < 1) {
            $this->state = self::STATE_FINISHED;

            return false;
        }

        $data = $this->queueManager->reserve($timeLeft);

        if (!$data) {
            return true;
        }

        $logger = $logger->withContext([
            'id' => $data->getId(),
        ]);
        ++$this->totalJobsCount;
        $payload = $data->getPayload();
        $jobStats = $this->queueManager->jobStats($data->getId());
        $attempt = $jobStats ? $jobStats->getReserves() : 1;
        $logger->log('info', 'Processing job', [
            'attempt' => $attempt,
        ]);
        $jobMeta = new JobMetadata(
            $data->getId(),
            $jobStats ? $jobStats->getReserves() : 1
        );

        try {
            $this->worker->work($payload, $jobMeta);
            $logger->log('info', 'Job performed successfully');
            ++$this->successfulJobsCount;
            $this->queueManager->delete($data->getId());
        } catch (\Exception $exception) {
            $this->handleException($exception, $payload, $jobMeta, $logger);
        }

        return true;
    }

    private function checkLimitsAndGetTimeLeft(int $reserveTimeout): int
    {
        if ($this->limits === null) {
            return $reserveTimeout;
        }

        if (
            ($jobsLimit = $this->limits->getJobsLimit()) > 0
            && $this->totalJobsCount >= $jobsLimit
        ) {
            $this->queueManager->getLogger()->log('info', 'Max jobs limit reached, stopping processing');

            return 0;
        }

        if (($timeLimit = $this->limits->getTimeLimit()) > 0) {
            $timeSpent = time() - $this->processStartTime;
            $timeLeft = $timeLimit - $timeSpent;

            if ($timeLeft < 1) {
                $this->queueManager->getLogger()->log('info', 'Max processing time limit reached, stopping processing');
            }

            return $timeLeft;
        }

        return $reserveTimeout;
    }

    private function handleException(
        \Exception $exception,
        $payload,
        JobMetadata $jobMeta,
        Logger $logger
    ): void {
        $logger->logException('Job failed', $exception);

        if (!$this->retryDelayProvider || !$this->worker instanceof RetriableWorkerInterface) {
            $logger->log('info', 'Retrying is not supported');
        } elseif (!$this->worker->isRetriable($exception)) {
            $logger->log('info', 'Retrying was not performed since the error is permanent');
        } elseif (($delay = $this->retryDelayProvider->getRetryDelay($jobMeta->getAttempt())) !== null) {
            $logger->log('info', 'Retrying', ['delay' => $delay]);
            $this->queueManager->release($jobMeta->getId(), $delay);

            return;
        } else {
            $logger->log('info', 'Retrying was not performed since the limit of attempts was reached');
        }

        $this->queueManager->delete($jobMeta->getId());

        if ($this->worker instanceof AdvancedWorkerInterface) {
            try {
                $this->worker->fail($payload, $jobMeta);
            } catch (\Exception $e) {
                $logger->logException("Got exception when the worker's fail action was running", $e);
            }
        }
    }
}
