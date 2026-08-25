<?php

namespace Tests\Feature;

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

class PhaseThreePreventiveApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_plan_requires_at_least_one_interval(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage']);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/maintenance-plans', [
                'name' => 'Plano freios',
                'component' => 'FREIOS',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'At least one preventive interval must be configured.');
    }

    public function test_maintenance_plan_stores_km_time_and_hourmeter_rules(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage']);
        $vehicle = $this->vehicleFor($user);

        $response = $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/maintenance-plans', [
                'name' => 'Revisao geral 10 mil',
                'component' => 'REVISAO_GERAL',
                'scope_type' => 'VEHICLE',
                'vehicle_id' => $vehicle->id,
                'interval_km' => 10000,
                'interval_days' => 180,
                'interval_hours' => 500,
            ])
            ->assertCreated()
            ->assertJsonPath('vehicle_id', $vehicle->id)
            ->assertJsonPath('interval_km', 10000)
            ->assertJsonPath('interval_days', 180);

        $this->assertDatabaseHas('maintenance_plans', [
            'id' => $response->json('id'),
            'component' => 'REVISAO_GERAL',
            'interval_km' => 10000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'maintenance_plans',
            'entity_id' => $response->json('id'),
            'origin' => 'api.v1.maintenance-plans.store',
        ]);
    }

    public function test_tachograph_near_expiration_creates_alert_and_insight(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage', 'mission-control.view']);
        $vehicle = $this->vehicleFor($user);
        $token = $this->tokenFor($user);

        $response = $this->withToken($token)
            ->postJson('/api/v1/tachographs', [
                'vehicle_id' => $vehicle->id,
                'number' => 'TAC-001',
                'manufacturer' => 'VDO',
                'model' => 'DTCO',
                'calibrated_at' => now()->subYear()->toDateString(),
                'certificate_number' => 'CERT-001',
                'expires_at' => now()->addDays(18)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('vehicle_id', $vehicle->id)
            ->assertJsonPath('number', 'TAC-001');

        $this->assertDatabaseHas('alerts', [
            'entity' => 'tachograph_records',
            'entity_id' => $response->json('id'),
            'rule' => 'tachograph-expiration',
            'severity' => 'MEDIO',
            'status' => 'ABERTO',
        ]);
        $this->assertDatabaseHas('insights', [
            'entity' => 'tachograph_records',
            'entity_id' => $response->json('id'),
            'type' => 'DOCUMENTAL',
            'status' => 'OPEN',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('alerts.open', 1)
            ->assertJsonPath('alerts.medium', 1)
            ->assertJsonPath('insights.open', 1)
            ->assertJsonPath('tachographs.next_90_days', 1);
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

    private function vehicleFor(User $user): Vehicle
    {
        return Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'TAC1234',
            'current_odometer_km' => 45000,
            'current_hourmeter' => 4820,
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
