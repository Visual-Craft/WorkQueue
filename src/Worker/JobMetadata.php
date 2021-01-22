<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\Worker;

class JobMetadata
{
    private string $id;

    private ?string $initId;

    private int $attempt;

    public function __construct(string $id, ?string $initId, int $attempt)
    {
        $this->id = $id;
        $this->initId = $initId;
        $this->attempt = $attempt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInitId(): ?string
    {
        return $this->initId;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
