<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue;

use VisualCraft\WorkQueue\QueueManager\AddOptions;

class JobAdder
{
    private QueueManager $queueManager;

    public function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    public function add($payload, ?int $delay = null): int
    {
        return $this->queueManager->add($payload, (new AddOptions())->setDelay($delay));
    }
}
