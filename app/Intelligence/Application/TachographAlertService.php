<?php

namespace App\Intelligence\Application;

use App\Models\Alert;
use App\Models\Insight;
use App\Models\TachographRecord;
use Carbon\CarbonImmutable;

class TachographAlertService
{
    public function sync(TachographRecord $record): void
    {
        $daysUntilExpiration = CarbonImmutable::today()->diffInDays(CarbonImmutable::parse($record->expires_at), false);

        if ($daysUntilExpiration > 90) {
            return;
        }

        $severity = $this->severityFor($daysUntilExpiration);
        $vehiclePlate = $record->vehicle?->plate ?? 'veiculo sem placa';
        $message = $daysUntilExpiration < 0
            ? "Tacografo do veiculo {$vehiclePlate} esta vencido."
            : "Tacografo do veiculo {$vehiclePlate} vence em {$daysUntilExpiration} dias.";
        $recommendation = $daysUntilExpiration < 0
            ? 'Bloquear programacao operacional ate regularizar a afericao.'
            : 'Antecipar afericao antes da janela operacional de vencimento.';
        $evidence = [
            'vehicle_id' => $record->vehicle_id,
            'vehicle_plate' => $vehiclePlate,
            'tachograph_number' => $record->number,
            'expires_at' => $record->expires_at?->toDateString(),
            'days_until_expiration' => $daysUntilExpiration,
        ];

        Alert::updateOrCreate(
            [
                'company_id' => $record->company_id,
                'entity' => 'tachograph_records',
                'entity_id' => $record->id,
                'rule' => 'tachograph-expiration',
                'status' => 'ABERTO',
            ],
            [
                'group_id' => $record->group_id,
                'branch_id' => $record->branch_id,
                'type' => 'TACHOGRAPH_EXPIRATION',
                'category' => 'COMPLIANCE',
                'severity' => $severity,
                'alert_date' => now(),
                'predicted_date' => $record->expires_at,
                'deadline' => $record->expires_at,
                'origin' => 'preventive-intelligence-engine',
                'message' => $message,
                'recommendation' => $recommendation,
                'evidence' => $evidence,
            ],
        );

        Insight::updateOrCreate(
            [
                'company_id' => $record->company_id,
                'entity' => 'tachograph_records',
                'entity_id' => $record->id,
                'type' => 'DOCUMENTAL',
                'status' => 'OPEN',
            ],
            [
                'group_id' => $record->group_id,
                'branch_id' => $record->branch_id,
                'title' => 'Vencimento de tacografo',
                'description' => $message,
                'evidence' => $evidence,
                'impact' => $severity,
                'probability' => 0.95,
                'confidence' => 0.9,
                'priority' => $this->priorityFor($severity),
                'recommendation' => $recommendation,
            ],
        );
    }

    private function severityFor(int $daysUntilExpiration): string
    {
        return match (true) {
            $daysUntilExpiration < 0 => 'CRITICO',
            $daysUntilExpiration <= 7 => 'ALTO',
            $daysUntilExpiration <= 30 => 'MEDIO',
            default => 'ATENCAO',
        };
    }

    private function priorityFor(string $severity): int
    {
        return match ($severity) {
            'CRITICO' => 100,
            'ALTO' => 80,
            'MEDIO' => 60,
            default => 40,
        };
    }
}
