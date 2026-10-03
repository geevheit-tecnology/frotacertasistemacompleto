<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseFourWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_execution_creates_task_notification_and_outbox_once(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage']);
        $token = $this->tokenFor($user);
        $entityId = (string) Str::uuid();

        $ruleId = $this->withToken($token)->postJson('/api/v1/rules', [
            'name' => 'Documento vencendo cria tarefa',
            'trigger_event' => 'document.expiring.v1',
            'conditions' => ['days_until_due' => ['lte' => 30]],
            'actions' => [
                ['type' => 'create_task', 'title' => 'Regularizar documento', 'priority' => 'HIGH'],
                ['type' => 'notify_internal', 'title' => 'Documento vencendo', 'message' => 'Acionar responsavel.'],
            ],
            'severity' => 'ALTO',
            'priority' => 80,
        ])->assertCreated()->json('id');

        $payload = [
            'entity' => 'vehicle_documents',
            'entity_id' => $entityId,
            'payload' => ['days_until_due' => 18],
        ];

        $firstExecutionId = $this->withToken($token)
            ->postJson('/api/v1/rules/'.$ruleId.'/execute', $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'MATCHED')
            ->json('id');

        $secondExecutionId = $this->withToken($token)
            ->postJson('/api/v1/rules/'.$ruleId.'/execute', $payload)
            ->assertCreated()
            ->json('id');

        $this->assertSame($firstExecutionId, $secondExecutionId);
        $this->assertDatabaseCount('rule_executions', 1);
        $this->assertDatabaseHas('tasks', [
            'rule_execution_id' => $firstExecutionId,
            'title' => 'Regularizar documento',
            'priority' => 'HIGH',
            'status' => 'OPEN',
        ]);
        $this->assertDatabaseHas('notifications', [
            'title' => 'Documento vencendo',
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'rule.executed.v1',
            'aggregate_type' => 'rules',
            'aggregate_id' => $ruleId,
            'status' => 'PENDING',
        ]);
    }

    public function test_alert_can_generate_action_task_with_audit_and_mission_control_count(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage', 'mission-control.view']);
        $alert = Alert::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'type' => 'DOCUMENT_EXPIRING',
            'category' => 'COMPLIANCE',
            'entity' => 'vehicle_documents',
            'entity_id' => (string) Str::uuid(),
            'severity' => 'ALTO',
            'alert_date' => now(),
            'deadline' => now()->addDays(7),
            'origin' => 'test',
            'rule' => 'document-expiring',
            'message' => 'Documento vence em 7 dias.',
            'recommendation' => 'Regularizar documento.',
            'status' => 'ABERTO',
            'evidence' => [],
        ]);
        $token = $this->tokenFor($user);

        $taskId = $this->withToken($token)
            ->postJson('/api/v1/alerts/'.$alert->id.'/tasks', ['title' => 'Resolver documento'])
            ->assertCreated()
            ->assertJsonPath('alert_id', $alert->id)
            ->assertJsonPath('priority', 'HIGH')
            ->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'tasks',
            'entity_id' => $taskId,
            'origin' => 'api.v1.alerts.tasks.store',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('tasks.open', 1);
    }

    public function test_rule_execution_can_block_vehicle_and_create_work_order_automatically(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'plate' => 'BLK1234',
            'status' => 'ACTIVE',
            'current_odometer_km' => 45000,
        ]);

        $ruleId = $this->withToken($token)->postJson('/api/v1/rules', [
            'name' => 'Checklist critico bloqueia veiculo e abre OS',
            'trigger_event' => 'checklist.nonconformity.created.v1',
            'actions' => [
                ['type' => 'block_vehicle'],
                [
                    'type' => 'create_work_order',
                    'number' => 'AUTO-BLK-001',
                    'work_order_type' => 'CORRECTIVE',
                    'priority' => 'CRITICAL',
                    'description' => 'Inspecionar nao conformidade critica.',
                ],
            ],
            'severity' => 'CRITICO',
            'priority' => 100,
        ])->assertCreated()->json('id');

        $executionId = $this->withToken($token)
            ->postJson('/api/v1/rules/'.$ruleId.'/execute', [
                'entity' => 'vehicles',
                'entity_id' => $vehicle->id,
                'payload' => ['vehicle_id' => $vehicle->id],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'MATCHED')
            ->json('id');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'BLOCKED',
        ]);
        $this->assertDatabaseHas('work_orders', [
            'company_id' => $user->company_id,
            'vehicle_id' => $vehicle->id,
            'number' => 'AUTO-BLK-001',
            'type' => 'CORRECTIVE',
            'priority' => 'CRITICAL',
            'status' => 'OPEN',
        ]);
        $this->assertDatabaseHas('rule_executions', [
            'id' => $executionId,
            'status' => 'MATCHED',
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'vehicle.blocked.v1',
            'aggregate_id' => $vehicle->id,
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'maintenance.order.created.v1',
            'aggregate_type' => 'work_orders',
            'status' => 'PENDING',
        ]);
    }

    public function test_rule_requiring_approval_does_not_run_critical_actions_immediately(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'plate' => 'APR1234',
            'status' => 'ACTIVE',
            'current_odometer_km' => 12000,
        ]);

        $ruleId = $this->withToken($token)->postJson('/api/v1/rules', [
            'name' => 'Bloqueio exige aprovacao',
            'trigger_event' => 'document.expired.v1',
            'actions' => [
                ['type' => 'block_vehicle'],
                ['type' => 'create_work_order', 'number' => 'AUTO-APPROVAL-001'],
            ],
            'severity' => 'CRITICO',
            'requires_approval' => true,
        ])->assertCreated()->json('id');

        $this->withToken($token)
            ->postJson('/api/v1/rules/'.$ruleId.'/execute', [
                'entity' => 'vehicles',
                'entity_id' => $vehicle->id,
                'payload' => ['vehicle_id' => $vehicle->id],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'WAITING_APPROVAL')
            ->assertJsonPath('result_payload.actions', []);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseMissing('work_orders', [
            'number' => 'AUTO-APPROVAL-001',
        ]);
    }

    public function test_waiting_rule_execution_can_be_approved_and_then_run_critical_actions(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'plate' => 'OKA1234',
            'status' => 'ACTIVE',
            'current_odometer_km' => 60000,
        ]);

        $ruleId = $this->withToken($token)->postJson('/api/v1/rules', [
            'name' => 'Aprovacao cria bloqueio e OS',
            'trigger_event' => 'document.expired.v1',
            'actions' => [
                ['type' => 'block_vehicle'],
                ['type' => 'create_work_order', 'number' => 'AUTO-APPROVED-001', 'priority' => 'CRITICAL'],
            ],
            'severity' => 'CRITICO',
            'requires_approval' => true,
        ])->assertCreated()->json('id');

        $executionId = $this->withToken($token)
            ->postJson('/api/v1/rules/'.$ruleId.'/execute', [
                'entity' => 'vehicles',
                'entity_id' => $vehicle->id,
                'payload' => ['vehicle_id' => $vehicle->id],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'WAITING_APPROVAL')
            ->json('id');

        $this->withToken($token)
            ->postJson('/api/v1/rule-executions/'.$executionId.'/approve')
            ->assertOk()
            ->assertJsonPath('status', 'APPROVED')
            ->assertJsonPath('result_payload.approved_by', $user->id);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'BLOCKED',
        ]);
        $this->assertDatabaseHas('work_orders', [
            'number' => 'AUTO-APPROVED-001',
            'vehicle_id' => $vehicle->id,
            'status' => 'OPEN',
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'vehicle.blocked.v1',
            'aggregate_id' => $vehicle->id,
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'maintenance.order.created.v1',
            'aggregate_type' => 'work_orders',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'rule_executions',
            'entity_id' => $executionId,
            'origin' => 'api.v1.rule-executions.approve',
        ]);
    }

    public function test_waiting_rule_execution_can_be_rejected_without_running_actions(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'plate' => 'REJ1234',
            'status' => 'ACTIVE',
            'current_odometer_km' => 35000,
        ]);

        $ruleId = $this->withToken($token)->postJson('/api/v1/rules', [
            'name' => 'Bloqueio recusavel',
            'trigger_event' => 'document.expired.v1',
            'actions' => [
                ['type' => 'block_vehicle'],
                ['type' => 'create_work_order', 'number' => 'AUTO-REJECTED-001'],
            ],
            'severity' => 'CRITICO',
            'requires_approval' => true,
        ])->assertCreated()->json('id');

        $executionId = $this->withToken($token)
            ->postJson('/api/v1/rules/'.$ruleId.'/execute', [
                'entity' => 'vehicles',
                'entity_id' => $vehicle->id,
                'payload' => ['vehicle_id' => $vehicle->id],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'WAITING_APPROVAL')
            ->json('id');

        $this->withToken($token)
            ->getJson('/api/v1/rule-executions?status=WAITING_APPROVAL')
            ->assertOk()
            ->assertJsonPath('data.0.id', $executionId);

        $this->withToken($token)
            ->postJson('/api/v1/rule-executions/'.$executionId.'/reject', [
                'reason' => 'Documento sera regularizado manualmente.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'REJECTED')
            ->assertJsonPath('result_payload.rejected_by', $user->id)
            ->assertJsonPath('result_payload.rejection_reason', 'Documento sera regularizado manualmente.');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseMissing('work_orders', [
            'number' => 'AUTO-REJECTED-001',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'rule_executions',
            'entity_id' => $executionId,
            'origin' => 'api.v1.rule-executions.reject',
        ]);
    }

    public function test_evidence_reference_can_be_registered_and_listed(): void
    {
        $user = $this->createUserWithPermissions(['workflow.manage']);
        $token = $this->tokenFor($user);
        $entityId = (string) Str::uuid();

        $evidenceId = $this->withToken($token)
            ->postJson('/api/v1/evidences', [
                'entity' => 'work_orders',
                'entity_id' => $entityId,
                'type' => 'PHOTO',
                'file_name' => 'foto-os.jpg',
                'mime_type' => 'image/jpeg',
                'storage_path' => 'evidences/work-orders/foto-os.jpg',
                'metadata' => ['source' => 'mobile'],
            ])
            ->assertCreated()
            ->assertJsonPath('entity', 'work_orders')
            ->json('id');

        $this->withToken($token)
            ->getJson('/api/v1/evidences?entity=work_orders&entity_id='.$entityId)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $evidenceId);
    }

    /**
     * @param array<int, string> $permissions
     */
    private function createUserWithPermissions(array $permissions): User
    {
        $group = Group::create(['name' => 'Grupo Teste']);
        $company = Company::create(['group_id' => $group->id, 'legal_name' => 'Empresa Teste']);
        $user = User::create([
            'group_id' => $group->id,
            'company_id' => $company->id,
            'name' => 'Gestor',
            'email' => Str::random(8).'@example.com',
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);

        $role = Role::create(['name' => 'role_'.Str::random(8)]);
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission);
        }
        $user->roles()->attach($role);

        return $user->refresh()->load('roles.permissions');
    }

    private function tokenFor(User $user): string
    {
        $plain = Str::random(80);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
        ]);

        return $plain;
    }
}
