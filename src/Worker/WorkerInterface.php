<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\Worker;

interface WorkerInterface
{
    public function work($payload, JobMetadata $metadata): void;
}
