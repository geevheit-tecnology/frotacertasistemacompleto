<?php

declare(strict_types=1);

namespace FleetIntelligence\Shared\Application\Outbox;

use FleetIntelligence\Shared\Domain\DomainEvent;

final readonly class OutboxMessage
{
    public function __construct(
        public string $eventName,
        public string $aggregateType,
        public string $aggregateId,
        public string $idempotencyKey,
        public array $payload,
    ) {
    }

    public static function fromDomainEvent(DomainEvent $event): self
    {
        return new self(
            eventName: $event->name(),
            aggregateType: $event->aggregateType(),
            aggregateId: $event->aggregateId(),
            idempotencyKey: hash('sha256', $event->name() . ':' . $event->aggregateType() . ':' . $event->aggregateId() . ':' . json_encode($event->payload())),
            payload: $event->payload(),
        );
    }
}
