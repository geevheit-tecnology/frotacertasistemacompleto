<?php

namespace App\Checklist\Application;

use App\Models\Alert;
use App\Models\ChecklistExecution;
use App\Models\ChecklistResponse;
use App\Models\NonConformity;
use App\Models\Task;
use App\Models\Vehicle;
use App\Workflow\Application\OutboxPublisher;
use Illuminate\Support\Facades\DB;

class ChecklistProcessor
{
    public function __construct(private readonly OutboxPublisher $outbox)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function complete(object $user, Vehicle $vehicle, array $data): ChecklistExecution
    {
        return DB::transaction(function () use ($user, $vehicle, $data): ChecklistExecution {
            $execution = ChecklistExecution::create([
                'group_id' => $user->group_id,
                'company_id' => $user->company_id,
                'branch_id' => $vehicle->branch_id,
                'template_id' => $data['template_id'] ?? null,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $data['driver_id'] ?? null,
                'odometer_km' => $data['odometer_km'],
                'hourmeter' => $data['hourmeter'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'performed_at' => $data['performed_at'] ?? now(),
                'status' => 'COMPLETED',
                'metadata' => $data['metadata'] ?? [],
            ]);

            $vehicle->current_odometer_km = max((int) $vehicle->current_odometer_km, (int) $data['odometer_km']);
            if (isset($data['hourmeter'])) {
                $vehicle->current_hourmeter = max((int) ($vehicle->current_hourmeter ?? 0), (int) $data['hourmeter']);
            }
            $vehicle->save();

            foreach ($data['responses'] as $response) {
                ChecklistResponse::create([
                    'checklist_execution_id' => $execution->id,
                    'item_key' => $response['item_key'],
                    'label' => $response['label'],
                    'answer' => $response['answer'],
                    'severity' => $response['severity'] ?? 'INFO',
                    'notes' => $response['notes'] ?? null,
                    'evidence_paths' => $response['evidence_paths'] ?? [],
                ]);

                if ($this->isActionable($response['severity'] ?? 'INFO')) {
                    $this->createNonConformityFlow($user, $vehicle, $execution, $response);
                }
            }

            $this->outbox->publish(
                'checklist.completed.v1',
                'checklist_executions',
                $execution->id,
                [
                    'checklist_execution_id' => $execution->id,
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $data['driver_id'] ?? null,
                    'odometer_km' => $data['odometer_km'],
                ],
            );

            return $execution->load(['responses', 'nonConformities']);
        });
    }

    /**
     * @param array<string, mixed> $response
     */
    private function createNonConformityFlow(object $user, Vehicle $vehicle, ChecklistExecution $execution, array $response): void
    {
        $severity = $response['severity'] ?? 'INFO';
        $description = $response['notes'] ?? $response['label'];

        $nonConformity = NonConformity::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $vehicle->branch_id,
            'checklist_execution_id' => $execution->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $execution->driver_id,
            'item_key' => $response['item_key'],
            'description' => $description,
            'severity' => $severity,
            'status' => 'OPEN',
            'evidence_paths' => $response['evidence_paths'] ?? [],
        ]);

        $alert = Alert::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $vehicle->branch_id,
            'type' => 'CHECKLIST_NON_CONFORMITY',
            'category' => 'CHECKLIST',
            'entity' => 'non_conformities',
            'entity_id' => $nonConformity->id,
            'severity' => $severity,
            'alert_date' => now(),
            'deadline' => now()->addDay(),
            'origin' => 'checklist',
            'rule' => 'checklist-nonconformity',
            'message' => 'Nao conformidade no checklist do veiculo '.$vehicle->plate.'.',
            'recommendation' => 'Avaliar item '.$response['label'].' e definir acao operacional.',
            'status' => 'ABERTO',
            'evidence' => [
                'checklist_execution_id' => $execution->id,
                'item_key' => $response['item_key'],
                'answer' => $response['answer'],
                'evidence_paths' => $response['evidence_paths'] ?? [],
            ],
        ]);

        Task::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $vehicle->branch_id,
            'alert_id' => $alert->id,
            'title' => 'Resolver nao conformidade de checklist',
            'description' => $description,
            'entity' => 'non_conformities',
            'entity_id' => $nonConformity->id,
            'priority' => $severity === 'CRITICO' ? 'CRITICAL' : 'HIGH',
            'due_at' => now()->addDay(),
            'origin' => 'checklist',
            'status' => 'OPEN',
        ]);

        $this->outbox->publish(
            'checklist.nonconformity.created.v1',
            'non_conformities',
            $nonConformity->id,
            [
                'non_conformity_id' => $nonConformity->id,
                'checklist_execution_id' => $execution->id,
                'vehicle_id' => $vehicle->id,
                'severity' => $severity,
            ],
        );
    }

    private function isActionable(string $severity): bool
    {
        return in_array($severity, ['ATENCAO', 'MEDIO', 'ALTO', 'CRITICO'], true);
    }
}
