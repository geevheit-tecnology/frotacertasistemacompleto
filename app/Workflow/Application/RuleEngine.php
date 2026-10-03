<?php

namespace App\Workflow\Application;

use App\Models\Alert;
use App\Models\Notification;
use App\Models\Rule;
use App\Models\RuleExecution;
use App\Models\Task;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class RuleEngine
{
    public function __construct(private readonly OutboxPublisher $outbox)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(Rule $rule, string $entity, string $entityId, array $payload): RuleExecution
    {
        $idempotencyKey = hash('sha256', $rule->id.':'.$entity.':'.$entityId.':'.json_encode($payload));

        $existing = RuleExecution::query()
            ->where('company_id', $rule->company_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($rule, $entity, $entityId, $payload, $idempotencyKey): RuleExecution {
            $result = [
                'actions' => [],
                'requires_approval' => $rule->requires_approval,
            ];

            $execution = RuleExecution::create([
                'group_id' => $rule->group_id,
                'company_id' => $rule->company_id,
                'branch_id' => $rule->branch_id,
                'rule_id' => $rule->id,
                'trigger_event' => $rule->trigger_event,
                'entity' => $entity,
                'entity_id' => $entityId,
                'input_payload' => $payload,
                'result_payload' => $result,
                'status' => $rule->requires_approval ? 'WAITING_APPROVAL' : 'MATCHED',
                'idempotency_key' => $idempotencyKey,
                'executed_at' => now(),
            ]);

            if (! $rule->requires_approval) {
                $result['actions'] = $this->runActions($rule, $execution, $entity, $entityId, $payload);
            }

            $execution->result_payload = $result;
            $execution->save();
            $this->outbox->publish(
                'rule.executed.v1',
                'rules',
                $rule->id,
                [
                    'rule_id' => $rule->id,
                    'rule_execution_id' => $execution->id,
                    'entity' => $entity,
                    'entity_id' => $entityId,
                    'actions' => $result['actions'],
                ],
                $idempotencyKey.':outbox',
            );

            $this->publishActionEvents($rule, $execution, $result['actions'], $idempotencyKey);

            return $execution;
        });
    }

    public function approve(RuleExecution $execution, string $approverId): RuleExecution
    {
        return DB::transaction(function () use ($execution, $approverId): RuleExecution {
            $execution = RuleExecution::query()->lockForUpdate()->whereKey($execution->id)->firstOrFail();

            if ($execution->status !== 'WAITING_APPROVAL') {
                return $execution;
            }

            $rule = Rule::query()->whereKey($execution->rule_id)->firstOrFail();
            $result = $execution->result_payload ?? [];
            $actions = $this->runActions($rule, $execution, $execution->entity, $execution->entity_id, $execution->input_payload ?? []);
            $result['actions'] = $actions;
            $result['approved_by'] = $approverId;
            $result['approved_at'] = now()->toISOString();

            $execution->forceFill([
                'status' => 'APPROVED',
                'result_payload' => $result,
            ])->save();

            $this->publishActionEvents($rule, $execution, $actions, $execution->idempotency_key.':approved');

            return $execution->refresh();
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $action
     */
    public function createTaskFromAlert(Alert $alert, array $payload = [], array $action = []): Task
    {
        return Task::firstOrCreate(
            [
                'company_id' => $alert->company_id,
                'alert_id' => $alert->id,
                'origin' => 'alert-action',
            ],
            [
                'group_id' => $alert->group_id,
                'branch_id' => $alert->branch_id,
                'assignee_id' => $alert->assignee_id,
                'title' => $action['title'] ?? $alert->message,
                'description' => $action['description'] ?? $alert->recommendation,
                'entity' => $alert->entity,
                'entity_id' => $alert->entity_id,
                'priority' => $this->priorityFor($alert->severity),
                'due_at' => $alert->deadline,
                'status' => 'OPEN',
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $action
     */
    private function createTask(Rule $rule, RuleExecution $execution, string $entity, string $entityId, array $payload, array $action): Task
    {
        return Task::create([
            'group_id' => $rule->group_id,
            'company_id' => $rule->company_id,
            'branch_id' => $rule->branch_id,
            'rule_execution_id' => $execution->id,
            'title' => $action['title'] ?? $rule->name,
            'description' => $action['description'] ?? null,
            'entity' => $entity,
            'entity_id' => $entityId,
            'priority' => $action['priority'] ?? $this->priorityFor($rule->severity),
            'due_at' => $action['due_at'] ?? null,
            'origin' => 'rule-engine',
            'status' => 'OPEN',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $action
     */
    private function createNotification(Rule $rule, RuleExecution $execution, array $payload, array $action): Notification
    {
        return Notification::create([
            'group_id' => $rule->group_id,
            'company_id' => $rule->company_id,
            'branch_id' => $rule->branch_id,
            'user_id' => $action['user_id'] ?? null,
            'channel' => 'INTERNAL',
            'title' => $action['title'] ?? $rule->name,
            'message' => $action['message'] ?? 'Regra operacional executada.',
            'payload' => [
                'rule_id' => $rule->id,
                'rule_execution_id' => $execution->id,
                'input' => $payload,
            ],
            'status' => 'PENDING',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, string>>
     */
    private function runActions(Rule $rule, RuleExecution $execution, string $entity, string $entityId, array $payload): array
    {
        $results = [];

        foreach ($rule->actions ?? [] as $action) {
            $type = $action['type'] ?? null;
            if ($type === 'create_task') {
                $task = $this->createTask($rule, $execution, $entity, $entityId, $payload, $action);
                $results[] = ['type' => 'create_task', 'task_id' => $task->id];
            }
            if ($type === 'notify_internal') {
                $notification = $this->createNotification($rule, $execution, $payload, $action);
                $results[] = ['type' => 'notify_internal', 'notification_id' => $notification->id];
            }
            if ($type === 'block_vehicle') {
                $vehicle = $this->changeVehicleStatus($rule, $entity, $entityId, $payload, 'BLOCKED');
                $results[] = ['type' => 'block_vehicle', 'vehicle_id' => $vehicle->id];
            }
            if ($type === 'release_vehicle') {
                $vehicle = $this->changeVehicleStatus($rule, $entity, $entityId, $payload, 'ACTIVE');
                $results[] = ['type' => 'release_vehicle', 'vehicle_id' => $vehicle->id];
            }
            if ($type === 'create_work_order') {
                $workOrder = $this->createWorkOrder($rule, $execution, $entity, $entityId, $payload, $action);
                $results[] = ['type' => 'create_work_order', 'work_order_id' => $workOrder->id];
            }
        }

        return $results;
    }

    /**
     * @param array<int, array<string, string>> $actions
     */
    private function publishActionEvents(Rule $rule, RuleExecution $execution, array $actions, string $idempotencyKey): void
    {
        foreach ($actions as $actionResult) {
            if (($actionResult['type'] ?? null) === 'block_vehicle') {
                $this->outbox->publish(
                    'vehicle.blocked.v1',
                    'vehicles',
                    $actionResult['vehicle_id'],
                    [
                        'rule_id' => $rule->id,
                        'rule_execution_id' => $execution->id,
                        'reason' => $rule->name,
                    ],
                    $idempotencyKey.':vehicle-blocked',
                );
            }
            if (($actionResult['type'] ?? null) === 'release_vehicle') {
                $this->outbox->publish(
                    'vehicle.released.v1',
                    'vehicles',
                    $actionResult['vehicle_id'],
                    [
                        'rule_id' => $rule->id,
                        'rule_execution_id' => $execution->id,
                        'reason' => $rule->name,
                    ],
                    $idempotencyKey.':vehicle-released',
                );
            }
            if (($actionResult['type'] ?? null) === 'create_work_order') {
                $this->outbox->publish(
                    'maintenance.order.created.v1',
                    'work_orders',
                    $actionResult['work_order_id'],
                    [
                        'rule_id' => $rule->id,
                        'rule_execution_id' => $execution->id,
                    ],
                    $idempotencyKey.':work-order-created',
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function changeVehicleStatus(Rule $rule, string $entity, string $entityId, array $payload, string $status): Vehicle
    {
        $vehicleId = $payload['vehicle_id'] ?? ($entity === 'vehicles' ? $entityId : null);

        $vehicle = Vehicle::query()
            ->where('company_id', $rule->company_id)
            ->when($rule->branch_id !== null, fn ($query) => $query->where('branch_id', $rule->branch_id))
            ->whereKey($vehicleId)
            ->firstOrFail();

        if ($vehicle->status !== $status) {
            $vehicle->forceFill(['status' => $status])->save();
        }

        return $vehicle;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $action
     */
    private function createWorkOrder(Rule $rule, RuleExecution $execution, string $entity, string $entityId, array $payload, array $action): WorkOrder
    {
        $vehicleId = $action['vehicle_id'] ?? $payload['vehicle_id'] ?? ($entity === 'vehicles' ? $entityId : null);

        $vehicle = Vehicle::query()
            ->where('company_id', $rule->company_id)
            ->when($rule->branch_id !== null, fn ($query) => $query->where('branch_id', $rule->branch_id))
            ->whereKey($vehicleId)
            ->firstOrFail();

        return WorkOrder::firstOrCreate(
            [
                'company_id' => $rule->company_id,
                'number' => $action['number'] ?? 'AUTO-'.$execution->id,
            ],
            [
                'group_id' => $rule->group_id,
                'branch_id' => $vehicle->branch_id,
                'vehicle_id' => $vehicle->id,
                'type' => $action['work_order_type'] ?? 'PREVENTIVE',
                'status' => 'OPEN',
                'priority' => $action['priority'] ?? $this->priorityFor($rule->severity),
                'description' => $action['description'] ?? $rule->name,
                'opened_at' => now(),
                'scheduled_at' => $action['scheduled_at'] ?? null,
                'labor_cost' => 0,
                'parts_cost' => 0,
                'total_cost' => 0,
            ],
        );
    }

    private function priorityFor(string $severity): string
    {
        return match ($severity) {
            'CRITICO' => 'CRITICAL',
            'ALTO' => 'HIGH',
            'MEDIO' => 'MEDIUM',
            default => 'LOW',
        };
    }
}
