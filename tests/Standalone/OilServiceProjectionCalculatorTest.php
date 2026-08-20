<?php

declare(strict_types=1);

use FleetIntelligence\Intelligence\Application\OilServiceProjectionCalculator;
use FleetIntelligence\Intelligence\Domain\OilServiceInput;
use FleetIntelligence\Shared\Domain\Severity;

return [
    'calculates predictive oil projection with medium severity' => function (): void {
        $projection = (new OilServiceProjectionCalculator())->calculate(new OilServiceInput(
            vehicleId: 'vehicle-1',
            vehiclePlate: 'ABC-1234',
            lastServiceKm: 120000,
            lastServiceDate: new DateTimeImmutable('2026-08-01'),
            currentKm: 129100,
            referenceDate: new DateTimeImmutable('2026-08-19'),
            configuredIntervalKm: 10000,
        ));

        assertSame(9100, $projection->kmSinceLastService);
        assertSame(900, $projection->remainingKm);
        assertSame(91.0, $projection->consumedPercentage);
        assertSame(18, $projection->daysSinceLastService);
        assertSame(505.56, $projection->averageDailyKm);
        assertSame(1.8, $projection->estimatedDaysToDue);
        assertSame(130000, $projection->nextServiceKm);
        assertSame(Severity::MEDIO, $projection->severity);
    },

    'marks overdue oil as critical' => function (): void {
        $projection = (new OilServiceProjectionCalculator())->calculate(new OilServiceInput(
            vehicleId: 'vehicle-2',
            vehiclePlate: 'DEF-5678',
            lastServiceKm: 50000,
            lastServiceDate: new DateTimeImmutable('2026-07-01'),
            currentKm: 60500,
            referenceDate: new DateTimeImmutable('2026-08-19'),
            configuredIntervalKm: 10000,
        ));

        assertSame(0, $projection->remainingKm);
        assertSame(Severity::CRITICO, $projection->severity);
    },

    'rejects inconsistent odometer data' => function (): void {
        expectException(InvalidArgumentException::class, static function (): void {
            (new OilServiceProjectionCalculator())->calculate(new OilServiceInput(
                vehicleId: 'vehicle-3',
                vehiclePlate: 'GHI-9012',
                lastServiceKm: 90000,
                lastServiceDate: new DateTimeImmutable('2026-08-01'),
                currentKm: 89000,
                referenceDate: new DateTimeImmutable('2026-08-19'),
                configuredIntervalKm: 10000,
            ));
        });
    },
];
