<?php

declare(strict_types=1);

namespace FleetIntelligence\Fleet\Domain;

use FleetIntelligence\Shared\Domain\TenantScope;

final readonly class Vehicle
{
    public function __construct(
        public string $id,
        public TenantScope $tenantScope,
        public string $plate,
        public string $fleetNumber,
        public VehicleStatus $status,
        public int $currentOdometerKm,
        public ?int $currentHourmeter = null,
    ) {
        if ($id === '' || $plate === '') {
            throw new \InvalidArgumentException('Vehicle requires id and plate.');
        }

        if ($currentOdometerKm < 0) {
            throw new \InvalidArgumentException('Vehicle odometer cannot be negative.');
        }
    }
}
