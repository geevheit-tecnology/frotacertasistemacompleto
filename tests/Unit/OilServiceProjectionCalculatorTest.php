<?php

namespace Tests\Unit;

use DateTimeImmutable;
use FleetIntelligence\Intelligence\Application\OilServiceProjectionCalculator;
use FleetIntelligence\Intelligence\Domain\OilServiceInput;
use FleetIntelligence\Shared\Domain\Severity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OilServiceProjectionCalculatorTest extends TestCase
{
    public function test_calculates_predictive_oil_projection_with_medium_severity(): void
    {
        $projection = (new OilServiceProjectionCalculator())->calculate(new OilServiceInput(
            vehicleId: 'vehicle-1',
            vehiclePlate: 'ABC-1234',
            lastServiceKm: 120000,
            lastServiceDate: new DateTimeImmutable('2026-08-01'),
            currentKm: 129100,
            referenceDate: new DateTimeImmutable('2026-08-19'),
            configuredIntervalKm: 10000,
        ));

        $this->assertSame(9100, $projection->kmSinceLastService);
        $this->assertSame(900, $projection->remainingKm);
        $this->assertSame(91.0, $projection->consumedPercentage);
        $this->assertSame(18, $projection->daysSinceLastService);
        $this->assertSame(505.56, $projection->averageDailyKm);
        $this->assertSame(1.8, $projection->estimatedDaysToDue);
        $this->assertSame(130000, $projection->nextServiceKm);
        $this->assertSame(Severity::MEDIO, $projection->severity);
    }

    public function test_marks_overdue_oil_as_critical(): void
    {
        $projection = (new OilServiceProjectionCalculator())->calculate(new OilServiceInput(
            vehicleId: 'vehicle-2',
            vehiclePlate: 'DEF-5678',
            lastServiceKm: 50000,
            lastServiceDate: new DateTimeImmutable('2026-07-01'),
            currentKm: 60500,
            referenceDate: new DateTimeImmutable('2026-08-19'),
            configuredIntervalKm: 10000,
        ));

        $this->assertSame(0, $projection->remainingKm);
        $this->assertSame(Severity::CRITICO, $projection->severity);
    }

    public function test_rejects_inconsistent_odometer_data(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OilServiceProjectionCalculator())->calculate(new OilServiceInput(
            vehicleId: 'vehicle-3',
            vehiclePlate: 'GHI-9012',
            lastServiceKm: 90000,
            lastServiceDate: new DateTimeImmutable('2026-08-01'),
            currentKm: 89000,
            referenceDate: new DateTimeImmutable('2026-08-19'),
            configuredIntervalKm: 10000,
        ));
    }
}
