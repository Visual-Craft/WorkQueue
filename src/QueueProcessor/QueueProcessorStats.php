<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueProcessor;

class QueueProcessorStats
{
    private int $totalJobsCount;

    private int $successfulJobsCount;

    public function __construct(int $totalJobsCount, int $successfulJobsCount)
    {
        $this->totalJobsCount = $totalJobsCount;
        $this->successfulJobsCount = $successfulJobsCount;
    }

    public function getTotalJobsCount(): int
    {
        return $this->totalJobsCount;
    }

    public function getSuccessfulJobsCount(): int
    {
        return $this->successfulJobsCount;
    }
}
