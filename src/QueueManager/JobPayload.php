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

    private ?int $initId;

    public function __construct($payload, int $attemptsCount, ?int $initId)
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

    public function getInitId(): ?int
    {
        return $this->initId;
    }
}
