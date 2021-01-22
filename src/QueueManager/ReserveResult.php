<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class ReserveResult
{
    private int $id;

    private JobPayload $job;

    public function __construct(int $id, JobPayload $job)
    {
        $this->id = $id;
        $this->job = $job;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getJob(): JobPayload
    {
        return $this->job;
    }
}
