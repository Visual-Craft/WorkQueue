<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class ReserveResult
{
    private int $id;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @param mixed $payload
     */
    public function __construct(int $id, $payload)
    {
        $this->id = $id;
        $this->payload = $payload;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
