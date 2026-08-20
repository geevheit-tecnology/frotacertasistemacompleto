<?php

declare(strict_types=1);

namespace FleetIntelligence\Intelligence\Application;

use FleetIntelligence\Intelligence\Domain\OilServiceInput;
use FleetIntelligence\Intelligence\Domain\OilServiceProjection;
use FleetIntelligence\Shared\Domain\Severity;

final class OilServiceProjectionCalculator
{
    public function calculate(OilServiceInput $input): OilServiceProjection
    {
        $kmSinceLastService = $input->currentKm - $input->lastServiceKm;
        $nextServiceKm = $input->lastServiceKm + $input->configuredIntervalKm;
        $remainingKm = max(0, $nextServiceKm - $input->currentKm);
        $consumedPercentage = round(($kmSinceLastService / $input->configuredIntervalKm) * 100, 2);
        $daysSinceLastService = max(0, (int) $input->lastServiceDate->diff($input->referenceDate)->format('%a'));
        $averageDailyKm = $daysSinceLastService === 0 ? 0.0 : round($kmSinceLastService / $daysSinceLastService, 2);
        $estimatedDaysToDue = $averageDailyKm <= 0.0 ? null : round($remainingKm / $averageDailyKm, 1);
        $severity = $this->severityFor($consumedPercentage, $remainingKm);

        return new OilServiceProjection(
            vehicleId: $input->vehicleId,
            vehiclePlate: $input->vehiclePlate,
            kmSinceLastService: $kmSinceLastService,
            remainingKm: $remainingKm,
            consumedPercentage: $consumedPercentage,
            daysSinceLastService: $daysSinceLastService,
            averageDailyKm: $averageDailyKm,
            estimatedDaysToDue: $estimatedDaysToDue,
            nextServiceKm: $nextServiceKm,
            severity: $severity,
            message: $this->messageFor($severity),
            recommendation: $this->recommendationFor($estimatedDaysToDue),
        );
    }

    private function severityFor(float $consumedPercentage, int $remainingKm): Severity
    {
        if ($remainingKm === 0 || $consumedPercentage >= 100.0) {
            return Severity::CRITICO;
        }

        if ($consumedPercentage >= 95.0) {
            return Severity::ALTO;
        }

        if ($consumedPercentage >= 90.0) {
            return Severity::MEDIO;
        }

        if ($consumedPercentage >= 80.0) {
            return Severity::ATENCAO;
        }

        return Severity::INFO;
    }

    private function messageFor(Severity $severity): string
    {
        return match ($severity) {
            Severity::CRITICO => 'Oleo vencido ou no limite configurado.',
            Severity::ALTO => 'Oleo em faixa alta de consumo do intervalo.',
            Severity::MEDIO => 'Oleo proximo do vencimento.',
            Severity::ATENCAO => 'Oleo entrou em faixa preventiva.',
            Severity::INFO => 'Oleo dentro do intervalo configurado.',
        };
    }

    private function recommendationFor(?float $estimatedDaysToDue): string
    {
        if ($estimatedDaysToDue === null) {
            return 'Acompanhar odometro ate haver historico suficiente para prever vencimento.';
        }

        if ($estimatedDaysToDue <= 0.0) {
            return 'Programar troca de oleo imediatamente e avaliar abertura de OS.';
        }

        return sprintf(
            'Mantendo a utilizacao atual, programar preventiva em aproximadamente %.1f dias.',
            $estimatedDaysToDue,
        );
    }
}
