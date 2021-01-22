<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\Worker;

interface RetriableWorkerInterface
{
    public function isRetriable(\Exception $exception): bool;
}
