<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class JobPayload
{
    /**
     * @var mixed
     */
    private $payload;

    private int $attempt;

    private ?string $initId;

    public function __construct($payload, int $attemptsCount, ?string $initId)
    {
        $this->payload = $payload;
        $this->attempt = $attemptsCount;
        $this->initId = $initId;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function getInitId(): ?string
    {
        return $this->initId;
    }
}
