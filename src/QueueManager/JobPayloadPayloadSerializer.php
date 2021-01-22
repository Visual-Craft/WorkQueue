<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue\QueueManager;

class JobPayloadPayloadSerializer implements JobPayloadSerializerInterface
{
    public function serialize(JobPayload $job): string
    {
        return serialize([
            $job->getPayload(),
            $job->getAttempt(),
            $job->getInitId(),
        ]);
    }

    public function unserialize(string $serialized): ?JobPayload
    {
        $data = unserialize($serialized, ['allowed_classes' => false]);

        if (!\is_array($data)) {
            return null;
        }

        $payload = $data[0] ?? null;
        $attemptsCount = $data[1] ?? null;
        $initialId = $data[2] ?? null;

        if (!\is_int($attemptsCount) || (!\is_string($initialId) && null !== $initialId)) {
            return null;
        }

        return new JobPayload($payload, $attemptsCount, $initialId);
    }
}
