<?php

namespace App\Workflow\Application;

use App\Models\Alert;
use App\Models\Notification;
use App\Models\Rule;
use App\Models\RuleExecution;
use App\Models\Task;
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

            foreach ($rule->actions ?? [] as $action) {
                $type = $action['type'] ?? null;
                if ($type === 'create_task') {
                    $task = $this->createTask($rule, $execution, $entity, $entityId, $payload, $action);
                    $result['actions'][] = ['type' => 'create_task', 'task_id' => $task->id];
                }
                if ($type === 'notify_internal') {
                    $notification = $this->createNotification($rule, $execution, $payload, $action);
                    $result['actions'][] = ['type' => 'notify_internal', 'notification_id' => $notification->id];
                }
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

            return $execution;
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
