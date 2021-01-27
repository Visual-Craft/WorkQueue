<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class JobPayloadPayloadSerializer implements JobPayloadSerializerInterface
{
    public function serialize($payload): string
    {
        return serialize($payload);
    }

    public function unserialize(string $serialized)
    {
        return unserialize($serialized, ['allowed_classes' => true]);
    }
}
