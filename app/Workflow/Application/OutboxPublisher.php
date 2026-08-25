<?php

namespace App\Workflow\Application;

use App\Models\Outbox;

class OutboxPublisher
{
    /**
     * @param array<string, mixed> $payload
     */
    public function publish(string $eventName, string $aggregateType, string $aggregateId, array $payload, ?string $idempotencyKey = null): Outbox
    {
        $key = $idempotencyKey ?? hash('sha256', $eventName.':'.$aggregateType.':'.$aggregateId.':'.json_encode($payload));

        return Outbox::firstOrCreate(
            ['idempotency_key' => $key],
            [
                'event_name' => $eventName,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'payload' => $payload,
                'status' => 'PENDING',
                'available_at' => now(),
            ],
        );
    }
}
