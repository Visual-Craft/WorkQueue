<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class JobStats
{
    private int $id;

    private string $queue;

    private string $state;

    private int $priority;

    private int $age;

    private int $delay;

    private int $ttr;

    private int $timeLeft;

    private int $file;

    private int $reserves;

    private int $timeouts;

    private int $releases;

    private int $buries;

    private int $kicks;

    public function __construct(
        int $id,
        string $queue,
        string $state,
        int $priority,
        int $age,
        int $delay,
        int $ttr,
        int $timeLeft,
        int $file,
        int $reserves,
        int $timeouts,
        int $releases,
        int $buries,
        int $kicks
    ) {
        $this->id = $id;
        $this->queue = $queue;
        $this->state = $state;
        $this->priority = $priority;
        $this->age = $age;
        $this->delay = $delay;
        $this->ttr = $ttr;
        $this->timeLeft = $timeLeft;
        $this->file = $file;
        $this->reserves = $reserves;
        $this->timeouts = $timeouts;
        $this->releases = $releases;
        $this->buries = $buries;
        $this->kicks = $kicks;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function getTtr(): int
    {
        return $this->ttr;
    }

    public function getTimeLeft(): int
    {
        return $this->timeLeft;
    }

    public function getFile(): int
    {
        return $this->file;
    }

    public function getReserves(): int
    {
        return $this->reserves;
    }

    public function getTimeouts(): int
    {
        return $this->timeouts;
    }

    public function getReleases(): int
    {
        return $this->releases;
    }

    public function getBuries(): int
    {
        return $this->buries;
    }

    public function getKicks(): int
    {
        return $this->kicks;
    }
}
