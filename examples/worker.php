<?php

namespace Examples;

require_once __DIR__ . '/../vendor/autoload.php';

use VisualCraft\WorkQueue\QueueProcessor\RetryDelayProvider;
use VisualCraft\WorkQueue\QueueProcessor;
use VisualCraft\WorkQueue\QueueProcessor\SchemeRetryDelayProvider;
use VisualCraft\WorkQueue\Worker\AdvancedWorkerInterface;
use VisualCraft\WorkQueue\Worker\JobMetadata;
use VisualCraft\WorkQueue\Worker\RetriableWorkerInterface;

// Define the worker
class SomeWorker implements AdvancedWorkerInterface, RetriableWorkerInterface
{
    public function work($payload, JobMetadata $metadata): void
    {
        echo "Processing\n";
        var_dump($payload);
        sleep(1);

        if ($payload === 'fail') {
            throw new \LogicException('soft fail');
        }

        if ($payload === 'hard_fail') {
            throw new \RuntimeException('hard fail');
        }
    }

    public function fail($payload, JobMetadata $metadata): void
    {
        echo "Handling fail\n";
    }

    public function isRetriable(\Exception $exception): bool
    {
        return $exception instanceof \LogicException;
    }
}

$manager = require __DIR__ . '/queue-manager.php';

// Create the queue processor and provide it with the worker
$processor = new QueueProcessor(
    $manager,
    new SomeWorker(),
    // Define retry scheme:
//    new SchemeRetryDelayProvider([3, 10]) // 2 retry attempts with 3 and 10 seconds delay
    new RetryDelayProvider(5, 3, 2.0) // 3 retry attempts
);

// Process the queue
while($processor->process()) {}
