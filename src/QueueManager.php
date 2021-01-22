<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job as PheanstalkJob;
use Pheanstalk\Pheanstalk;
use VisualCraft\WorkQueue\QueueManager\AddOptions;
use VisualCraft\WorkQueue\QueueManager\JobPayload;
use VisualCraft\WorkQueue\QueueManager\JobPayloadPayloadSerializer;
use VisualCraft\WorkQueue\QueueManager\JobPayloadSerializerInterface;
use VisualCraft\WorkQueue\QueueManager\ReserveResult;

class QueueManager
{
    private Pheanstalk $connection;

    private string $queueName;

    private Logger $logger;

    private int $ttr;

    private JobPayloadSerializerInterface $jobPayloadSerializer;

    public function __construct(
        Pheanstalk $connection,
        string $queueName,
        Logger $logger,
        int $ttr = 3600,
        ?JobPayloadSerializerInterface $jobPayloadSerializer = null
    ) {
        $this->connection = $connection;
        $this->queueName = $queueName;
        $this->logger = $logger->withContext(['queue' => $this->queueName]);
        $this->ttr = $ttr;

        if ($jobPayloadSerializer === null) {
            $this->jobPayloadSerializer = new JobPayloadPayloadSerializer();
        } else {
            $this->jobPayloadSerializer = $jobPayloadSerializer;
        }
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function add($payload, AddOptions $options): int
    {
        $logger = $this->logger;

        if ($options->getInitId() !== null) {
            $logger = $this->logger->withContext([
                'init_id' => $options->getInitId(),
            ]);
        }

        $logger->log('info', 'Adding job');

        try {
            $pheanstalkJob = $this->connection
                ->useTube($this->queueName)
                ->put(
                    $this->jobPayloadSerializer->serialize(
                        new JobPayload($payload, $options->getAttempt(), $options->getInitId())
                    ),
                    Pheanstalk::DEFAULT_PRIORITY,
                    $options->getDelay() ?: Pheanstalk::DEFAULT_DELAY,
                    $this->ttr
                )
            ;
        } catch (\Exception $e) {
            $logger->logException('Unable to add job', $e);

            throw $e;
        }

        $logger->log('info', 'Job successfully added', [
            'id' => $pheanstalkJob->getId(),
        ]);

        return $pheanstalkJob->getId();
    }

    public function reserve(int $timeout): ?ReserveResult
    {
        $logger = $this->logger;
        $logger->log('debug', 'Reserving job');
        $pheanstalkJob = $this->connection
            ->watch($this->queueName)
            ->reserveWithTimeout($timeout)
        ;

        if (!$pheanstalkJob) {
            $logger->log('debug', 'No jobs found');

            return null;
        }

        $logger = $logger->withContext([
            'id' => $pheanstalkJob->getId(),
        ]);
        $job = $this->jobPayloadSerializer->unserialize($pheanstalkJob->getData());

        if (!$job) {
            $logger->log('warning', 'Malformed job data, skipping');
            $this->delete($pheanstalkJob->getId());

            return null;
        }

        $logger->log('info', 'Job is reserved', $job->getInitId() !== null ? ['init_id' => $job->getInitId()] : []);

        return new ReserveResult($pheanstalkJob->getId(), $job);
    }

    public function delete(int $id): void
    {
        $this->connection->delete(new PheanstalkJob($id, ''));
    }

    public function clear(): void
    {
        $logger = $this->logger;
        $logger->log('info', 'Clearing started');
        $getJob = function ($method) {
            try {
                return $this->connection
                    ->useTube($this->queueName)
                    ->{$method}($this->queueName)
                ;
            } catch (ServerException $e) {
                if (strpos($e->getMessage(), 'NOT_FOUND:') === 0) {
                    return null;
                }

                throw $e;
            }
        };

        foreach (['peekReady', 'peekDelayed', 'peekBuried'] as $method) {
            $count = 0;

            while ($job = $getJob($method)) {
                $this->connection->delete($job);
                $count++;
            }

            $logger->log('info', sprintf('Cleared %s jobs', strtolower(str_replace('peek', '', $method))), [
                'count' => $count,
            ]);
        }

        $logger->log('info', 'Clearing done');
    }
}
