<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueProcessor;

interface RetryDelayProviderInterface
{
    public function getRetryDelay(int $retryAttempt): ?int;
}
