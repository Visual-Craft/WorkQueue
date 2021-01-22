<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueProcessor;

class RetryDelayProvider implements RetryDelayProviderInterface
{
    private int $initialDelay;

    private float $backoff;

    private int $maxAttempts;

    public function __construct(int $initialDelay, int $maxAttempts, float $backoff = 1.0)
    {
        $this->initialDelay = $initialDelay;
        $this->maxAttempts = $maxAttempts;
        $this->backoff = $backoff;
    }

    public function getRetryDelay(int $retryAttempt): ?int
    {
        if ($retryAttempt > $this->maxAttempts) {
            return null;
        }

        return $retryAttempt === 1
            ? $this->initialDelay
            : (int) ($this->initialDelay * ($this->backoff ** ($retryAttempt - 1)))
        ;
    }
}
