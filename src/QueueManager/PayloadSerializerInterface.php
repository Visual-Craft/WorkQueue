<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

interface PayloadSerializerInterface
{
    /**
     * @param mixed $payload
     */
    public function serialize($payload): string;

    /**
     * @return mixed
     */
    public function unserialize(string $serialized);
}
