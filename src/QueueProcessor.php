<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue;

use VisualCraft\WorkQueue\QueueManager\AddOptions;
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
        $job = $data->getJob();
        $logger->log('info', 'Processing job', [
            'attempt' => $job->getAttempt(),
        ]);
        $jobMeta = new JobMetadata(
            $data->getId(),
            $job->getInitId() ?: $data->getId(),
            $job->getAttempt()
        );

        try {
            $this->worker->work($job->getPayload(), $jobMeta);
            $logger->log('info', 'Job performed successfully');
            ++$this->successfulJobsCount;
        } catch (\Exception $exception) {
            $this->handleException($exception, $job->getPayload(), $jobMeta, $logger);
        } finally {
            $this->queueManager->delete($data->getId());
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
            $this->queueManager->add(
                $payload,
                (new AddOptions())
                    ->setDelay($delay)
                    ->setAttempt($jobMeta->getAttempt() + 1)
                    ->setInitId($jobMeta->getInitId())
            );

            return;
        } else {
            $logger->log('info', 'Retrying was not performed since the limit of attempts was reached');
        }

        if ($this->worker instanceof AdvancedWorkerInterface) {
            try {
                $this->worker->fail($payload, $jobMeta);
            } catch (\Exception $e) {
                $logger->logException("Got exception when the worker's fail action was running", $e);
            }
        }
    }
}
