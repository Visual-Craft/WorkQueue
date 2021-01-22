<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueProcessor;

class QueueProcessorLimits
{
    private int $timeLimit;

    private int $jobsLimit;

    public function __construct(int $timeLimit, int $jobsLimit)
    {
        $this->timeLimit = $timeLimit;
        $this->jobsLimit = $jobsLimit;
    }

    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }

    public function getJobsLimit(): int
    {
        return $this->jobsLimit;
    }
}
