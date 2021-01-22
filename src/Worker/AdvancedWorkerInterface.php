<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\Worker;

interface AdvancedWorkerInterface extends WorkerInterface
{
    public function fail($payload, JobMetadata $metadata): void;
}
