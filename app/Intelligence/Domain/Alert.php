<?php

declare(strict_types=1);

namespace FleetIntelligence\Intelligence\Domain;

use FleetIntelligence\Shared\Domain\AlertStatus;
use FleetIntelligence\Shared\Domain\Severity;

final readonly class Alert
{
    /**
     * @param array<string, mixed> $evidence
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $category,
        public string $entity,
        public string $entityId,
        public Severity $severity,
        public \DateTimeImmutable $date,
        public ?\DateTimeImmutable $predictedDate,
        public ?\DateTimeImmutable $deadline,
        public string $origin,
        public string $rule,
        public string $message,
        public string $recommendation,
        public AlertStatus $status,
        public ?string $assigneeId,
        public array $evidence,
    ) {
        if ($id === '' || $type === '' || $entity === '' || $entityId === '') {
            throw new \InvalidArgumentException('Alert requires identity, type and entity.');
        }
    }
}
