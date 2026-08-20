<?php

declare(strict_types=1);

namespace FleetIntelligence\Intelligence\Domain;

use FleetIntelligence\Shared\Domain\Severity;

final readonly class OilServiceProjection
{
    public function __construct(
        public string $vehicleId,
        public string $vehiclePlate,
        public int $kmSinceLastService,
        public int $remainingKm,
        public float $consumedPercentage,
        public int $daysSinceLastService,
        public float $averageDailyKm,
        public ?float $estimatedDaysToDue,
        public int $nextServiceKm,
        public Severity $severity,
        public string $message,
        public string $recommendation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function evidence(): array
    {
        return [
            'vehicle_id' => $this->vehicleId,
            'vehicle_plate' => $this->vehiclePlate,
            'km_since_last_service' => $this->kmSinceLastService,
            'remaining_km' => $this->remainingKm,
            'consumed_percentage' => $this->consumedPercentage,
            'days_since_last_service' => $this->daysSinceLastService,
            'average_daily_km' => $this->averageDailyKm,
            'estimated_days_to_due' => $this->estimatedDaysToDue,
            'next_service_km' => $this->nextServiceKm,
        ];
    }
}
