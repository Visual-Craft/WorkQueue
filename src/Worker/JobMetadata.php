<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\Worker;

class JobMetadata
{
    private int $id;

    private ?int $initId;

    private int $attempt;

    public function __construct(int $id, ?int $initId, int $attempt)
    {
        $this->id = $id;
        $this->initId = $initId;
        $this->attempt = $attempt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getInitId(): ?int
    {
        return $this->initId;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
