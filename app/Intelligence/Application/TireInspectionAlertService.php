<?php

namespace App\Intelligence\Application;

use App\Models\Alert;
use App\Models\Insight;
use App\Models\Tire;
use App\Models\TireInspection;

class TireInspectionAlertService
{
    public function sync(Tire $tire, TireInspection $inspection): void
    {
        $reason = $this->reasonFor($tire, $inspection);
        if ($reason === null) {
            return;
        }

        [$severity, $message, $recommendation, $priority] = $reason;

        Alert::updateOrCreate(
            [
                'company_id' => $tire->company_id,
                'entity' => 'tires',
                'entity_id' => $tire->id,
                'rule' => 'tire-preventive-inspection',
                'status' => 'ABERTO',
            ],
            [
                'group_id' => $tire->group_id,
                'branch_id' => $tire->branch_id,
                'type' => 'TIRE_PREVENTIVE',
                'category' => 'TIRES',
                'severity' => $severity,
                'alert_date' => now(),
                'deadline' => now()->addDays($severity === 'CRITICO' ? 1 : 7),
                'origin' => 'preventive-intelligence-engine',
                'message' => $message,
                'recommendation' => $recommendation,
                'evidence' => [
                    'tire_id' => $tire->id,
                    'fire_number' => $tire->fire_number,
                    'vehicle_id' => $tire->vehicle_id,
                    'position' => $inspection->position,
                    'tread_depth_mm' => $inspection->tread_depth_mm,
                    'pressure_psi' => $inspection->pressure_psi,
                    'wear_pattern' => $inspection->wear_pattern,
                    'photo_paths' => $inspection->photo_paths,
                ],
            ],
        );

        Insight::updateOrCreate(
            [
                'company_id' => $tire->company_id,
                'entity' => 'tires',
                'entity_id' => $tire->id,
                'type' => 'PNEU',
                'status' => 'OPEN',
            ],
            [
                'group_id' => $tire->group_id,
                'branch_id' => $tire->branch_id,
                'title' => 'Risco preventivo em pneu',
                'description' => $message,
                'evidence' => ['inspection_id' => $inspection->id],
                'impact' => $severity,
                'probability' => 0.8,
                'confidence' => $inspection->visual_confidence ?? 0.75,
                'priority' => $priority,
                'recommendation' => $recommendation,
            ],
        );
    }

    /**
     * @return array{string, string, string, int}|null
     */
    private function reasonFor(Tire $tire, TireInspection $inspection): ?array
    {
        if ((float) $inspection->tread_depth_mm <= 1.6) {
            return ['CRITICO', 'Pneu '.$tire->fire_number.' abaixo do limite minimo de sulco.', 'Remover o pneu de operacao e abrir manutencao.', 100];
        }
        if ((float) $inspection->tread_depth_mm <= 3.0) {
            return ['ALTO', 'Pneu '.$tire->fire_number.' proximo do limite de sulco.', 'Programar substituicao ou recapagem preventiva.', 80];
        }
        if ($inspection->wear_pattern !== null && $inspection->wear_pattern !== 'NORMAL') {
            return ['MEDIO', 'Pneu '.$tire->fire_number.' com desgaste irregular.', 'Inspecionar alinhamento, calibragem e posicao.', 60];
        }
        if ($inspection->pressure_psi !== null && (float) $inspection->pressure_psi < 80) {
            return ['MEDIO', 'Pneu '.$tire->fire_number.' com pressao abaixo do recomendado.', 'Calibrar e reavaliar perda de pressao.', 60];
        }

        return null;
    }
}
