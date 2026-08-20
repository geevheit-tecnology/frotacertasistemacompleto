<?php

declare(strict_types=1);

namespace FleetIntelligence\Shared\Domain;

interface DomainEvent
{
    public function name(): string;

    public function aggregateType(): string;

    public function aggregateId(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
