<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class PayloadSerializer implements PayloadSerializerInterface
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
