<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\Worker;

class JobMetadata
{
    private int $id;

    private int $attempt;

    public function __construct(int $id, int $attempt)
    {
        $this->id = $id;
        $this->attempt = $attempt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
