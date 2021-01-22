<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class AddOptions
{
    private ?int $delay = null;

    private ?string $initId = null;

    private int $attempt = 1;

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function setDelay(?int $value): self
    {
        $this->delay = $value;

        return $this;
    }

    public function getInitId(): ?string
    {
        return $this->initId;
    }

    public function setInitId(?string $value): self
    {
        $this->initId = $value;

        return $this;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function setAttempt(int $value): self
    {
        $this->attempt = $value;

        return $this;
    }
}
