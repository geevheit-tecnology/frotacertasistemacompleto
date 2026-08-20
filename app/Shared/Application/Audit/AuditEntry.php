<?php

declare(strict_types=1);

namespace FleetIntelligence\Shared\Application\Audit;

final readonly class AuditEntry
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function __construct(
        public string $actorId,
        public \DateTimeImmutable $occurredAt,
        public string $ipAddress,
        public string $entity,
        public string $entityId,
        public string $action,
        public ?array $before,
        public ?array $after,
        public string $origin,
    ) {
        if ($actorId === '' || $entity === '' || $entityId === '' || $action === '') {
            throw new \InvalidArgumentException('Audit entry requires actor, entity and action.');
        }
    }
}
