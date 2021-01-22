<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

interface JobPayloadSerializerInterface
{
    public function serialize(JobPayload $job): string;

    public function unserialize(string $serialized): ?JobPayload;
}
