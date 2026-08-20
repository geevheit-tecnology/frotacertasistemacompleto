<?php

declare(strict_types=1);

namespace FleetIntelligence\Intelligence\Domain;

final readonly class OilServiceInput
{
    public function __construct(
        public string $vehicleId,
        public string $vehiclePlate,
        public int $lastServiceKm,
        public \DateTimeImmutable $lastServiceDate,
        public int $currentKm,
        public \DateTimeImmutable $referenceDate,
        public int $configuredIntervalKm,
    ) {
        if ($vehicleId === '' || $vehiclePlate === '') {
            throw new \InvalidArgumentException('Oil projection requires vehicle identity.');
        }

        if ($lastServiceKm < 0 || $currentKm < 0 || $configuredIntervalKm <= 0) {
            throw new \InvalidArgumentException('Oil projection kilometers must be valid.');
        }

        if ($currentKm < $lastServiceKm) {
            throw new \InvalidArgumentException('Current kilometer cannot be lower than last service kilometer.');
        }

        if ($referenceDate < $lastServiceDate) {
            throw new \InvalidArgumentException('Reference date cannot be before last service date.');
        }
    }
}
