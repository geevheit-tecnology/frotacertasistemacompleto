<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
