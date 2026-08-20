<?php

namespace App\Intelligence\Application;

use App\Models\Alert;
use App\Models\Insight;
use App\Models\OilServiceRecord;
use FleetIntelligence\Intelligence\Domain\OilServiceProjection;
use FleetIntelligence\Shared\Domain\Severity;

class OilServiceAlertService
{
    public function sync(OilServiceRecord $record, OilServiceProjection $projection): void
    {
        if ($projection->severity === Severity::INFO) {
            return;
        }

        $predictedDate = $projection->estimatedDaysToDue === null
            ? null
            : now()->addHours((int) round($projection->estimatedDaysToDue * 24));

        Alert::updateOrCreate(
            [
                'company_id' => $record->company_id,
                'entity' => 'vehicles',
                'entity_id' => $record->vehicle_id,
                'rule' => 'oil-service-projection',
                'status' => 'ABERTO',
            ],
            [
                'group_id' => $record->group_id,
                'branch_id' => $record->branch_id,
                'type' => 'OIL_SERVICE_DUE',
                'category' => 'MAINTENANCE',
                'severity' => $projection->severity->value,
                'alert_date' => now(),
                'predicted_date' => $predictedDate,
                'deadline' => $predictedDate,
                'origin' => 'preventive-intelligence-engine',
                'message' => $projection->message,
                'recommendation' => $projection->recommendation,
                'evidence' => $projection->evidence(),
            ],
        );

        Insight::updateOrCreate(
            [
                'company_id' => $record->company_id,
                'entity' => 'vehicles',
                'entity_id' => $record->vehicle_id,
                'type' => 'OIL_SERVICE_FORECAST',
                'status' => 'OPEN',
            ],
            [
                'group_id' => $record->group_id,
                'branch_id' => $record->branch_id,
                'title' => 'Previsao de troca de oleo',
                'description' => $projection->message,
                'evidence' => $projection->evidence(),
                'impact' => $projection->severity->value,
                'probability' => 0.85,
                'confidence' => $projection->averageDailyKm > 0 ? 0.9 : 0.6,
                'priority' => $this->priorityFor($projection->severity),
                'recommendation' => $projection->recommendation,
            ],
        );
    }

    private function priorityFor(Severity $severity): int
    {
        return match ($severity) {
            Severity::CRITICO => 100,
            Severity::ALTO => 80,
            Severity::MEDIO => 60,
            Severity::ATENCAO => 40,
            Severity::INFO => 10,
        };
    }
}
