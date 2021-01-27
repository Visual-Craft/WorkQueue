<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job as PheanstalkJob;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Response\ArrayResponse;
use VisualCraft\WorkQueue\QueueManager\PayloadSerializer;
use VisualCraft\WorkQueue\QueueManager\PayloadSerializerInterface;
use VisualCraft\WorkQueue\QueueManager\JobStats;
use VisualCraft\WorkQueue\QueueManager\ReserveResult;

class QueueManager
{
    private Pheanstalk $connection;

    private string $queueName;

    private Logger $logger;

    private int $ttr;

    private PayloadSerializerInterface $payloadSerializer;

    public function __construct(
        Pheanstalk $connection,
        string $queueName,
        Logger $logger,
        int $ttr = 3600,
        ?PayloadSerializerInterface $payloadSerializer = null
    ) {
        $this->connection = $connection;
        $this->queueName = $queueName;
        $this->logger = $logger->withContext(['queue' => $this->queueName]);
        $this->ttr = $ttr;

        if ($payloadSerializer === null) {
            $this->payloadSerializer = new PayloadSerializer();
        } else {
            $this->payloadSerializer = $payloadSerializer;
        }
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function add($payload, ?int $delay = null): int
    {
        $logger = $this->logger;
        $logger->log('info', 'Adding job');

        try {
            $pheanstalkJob = $this->connection
                ->useTube($this->queueName)
                ->put(
                    $this->payloadSerializer->serialize($payload),
                    Pheanstalk::DEFAULT_PRIORITY,
                    $delay ?: Pheanstalk::DEFAULT_DELAY,
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

    public function release(int $id, ?int $delay = null): void
    {
        $this->connection->release(
            new PheanstalkJob($id, ''),
            Pheanstalk::DEFAULT_PRIORITY,
            $delay ?: Pheanstalk::DEFAULT_DELAY
        );
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
        $job = $this->payloadSerializer->unserialize($pheanstalkJob->getData());

        if (!$job) {
            $logger->log('warning', 'Malformed job data, skipping');
            $this->delete($pheanstalkJob->getId());

            return null;
        }

        $logger->log('info', 'Job is reserved');

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
                if ($this->isNotFoundServerException($e)) {
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

    public function jobStats(int $id): ?JobStats
    {
        $response = null;

        try {
            $response = $this->connection->statsJob(new PheanstalkJob($id, ''));
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ServerException $e) {
            if (!$this->isNotFoundServerException($e)) {
                throw $e;
            }
        }

        if (!$response instanceof ArrayResponse) {
            return null;
        }

        return new JobStats(
            (int) $response->id,
            (string) $response->tube,
            (string) $response->state,
            (int) $response->pri,
            (int) $response->age,
            (int) $response->delay,
            (int) $response->ttr,
            (int) $response->{'time-left'},
            (int) $response->file,
            (int) $response->reserves,
            (int) $response->timeouts,
            (int) $response->releases,
            (int) $response->buries,
            (int) $response->kicks,
        );
    }

    private function isNotFoundServerException(ServerException $e): bool
    {
        return strpos($e->getMessage(), 'NOT_FOUND') !== false;
    }
}
