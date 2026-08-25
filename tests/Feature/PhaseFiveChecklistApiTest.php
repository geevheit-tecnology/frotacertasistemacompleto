<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseFiveChecklistApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_completion_generates_non_conformity_alert_task_and_outbox(): void
    {
        $user = $this->createUserWithPermissions(['checklist.manage', 'mission-control.view']);
        $token = $this->tokenFor($user);
        $vehicle = $this->vehicleFor($user, 45000);
        $driver = $this->driverFor($user);

        $templateId = $this->withToken($token)
            ->postJson('/api/v1/checklist-templates', [
                'name' => 'Checklist Diario',
                'items' => [
                    ['key' => 'tires', 'label' => 'Pneus'],
                    ['key' => 'brakes', 'label' => 'Freios'],
                ],
            ])
            ->assertCreated()
            ->json('id');

        $executionId = $this->withToken($token)
            ->postJson('/api/v1/checklists', [
                'template_id' => $templateId,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'odometer_km' => 45120,
                'hourmeter' => 4900,
                'latitude' => -23.55052,
                'longitude' => -46.63331,
                'responses' => [
                    [
                        'item_key' => 'tires',
                        'label' => 'Pneus',
                        'answer' => 'NAO_CONFORME',
                        'severity' => 'CRITICO',
                        'notes' => 'Pneu dianteiro com limite critico.',
                        'evidence_paths' => ['checklists/tire-front.jpg'],
                    ],
                    [
                        'item_key' => 'brakes',
                        'label' => 'Freios',
                        'answer' => 'CONFORME',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('vehicle_id', $vehicle->id)
            ->assertJsonCount(2, 'responses')
            ->assertJsonCount(1, 'non_conformities')
            ->json('id');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'current_odometer_km' => 45120,
        ]);
        $this->assertDatabaseHas('non_conformities', [
            'checklist_execution_id' => $executionId,
            'vehicle_id' => $vehicle->id,
            'severity' => 'CRITICO',
            'status' => 'OPEN',
        ]);
        $this->assertDatabaseHas('alerts', [
            'type' => 'CHECKLIST_NON_CONFORMITY',
            'category' => 'CHECKLIST',
            'severity' => 'CRITICO',
            'status' => 'ABERTO',
        ]);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Resolver nao conformidade de checklist',
            'priority' => 'CRITICAL',
            'status' => 'OPEN',
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'checklist.completed.v1',
            'aggregate_type' => 'checklist_executions',
            'aggregate_id' => $executionId,
        ]);
        $this->assertDatabaseHas('outbox', [
            'event_name' => 'checklist.nonconformity.created.v1',
            'aggregate_type' => 'non_conformities',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('checklists.completed', 1)
            ->assertJsonPath('checklists.non_conformities_open', 1)
            ->assertJsonPath('checklists.critical_non_conformities', 1)
            ->assertJsonPath('tasks.open', 1);
    }

    public function test_checklist_rejects_odometer_lower_than_vehicle_current_odometer(): void
    {
        $user = $this->createUserWithPermissions(['checklist.manage']);
        $vehicle = $this->vehicleFor($user, 45000);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/checklists', [
                'vehicle_id' => $vehicle->id,
                'odometer_km' => 44999,
                'responses' => [
                    ['item_key' => 'lights', 'label' => 'Luzes', 'answer' => 'CONFORME'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Checklist odometer cannot be lower than current vehicle odometer.');
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

    private function vehicleFor(User $user, int $odometer): Vehicle
    {
        return Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'CHK1234',
            'current_odometer_km' => $odometer,
        ]);
    }

    private function driverFor(User $user): Driver
    {
        return Driver::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'name' => 'Motorista Checklist',
            'document_number' => '12345678900',
            'status' => 'ACTIVE',
        ]);
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
