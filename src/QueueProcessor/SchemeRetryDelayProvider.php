<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueProcessor;

class SchemeRetryDelayProvider implements RetryDelayProviderInterface
{
    private array $scheme;

    public function __construct(array $scheme)
    {
        $this->scheme = $scheme;
    }

    public function getRetryDelay(int $retryAttempt): ?int
    {
        return $this->scheme[$retryAttempt - 1] ?? null;
    }
}
