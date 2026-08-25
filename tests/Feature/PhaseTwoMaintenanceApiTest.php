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

class PhaseTwoMaintenanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_oil_service_requires_maintenance_permission(): void
    {
        $user = $this->createUserWithPermissions(['fleet.manage']);
        $vehicle = $this->vehicleFor($user);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/oil-services', [
                'vehicle_id' => $vehicle->id,
                'service_odometer_km' => 120000,
                'service_date' => now()->subDays(40)->toDateString(),
                'interval_km' => 10000,
            ])
            ->assertForbidden();
    }

    public function test_oil_service_creation_returns_predictive_projection_and_audit(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage']);
        $vehicle = $this->vehicleFor($user, currentOdometerKm: 129100);

        $response = $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/oil-services', [
                'vehicle_id' => $vehicle->id,
                'engine' => 'Cummins ISB',
                'oil_type' => 'synthetic',
                'viscosity' => '15W40',
                'manufacturer' => 'Mobil',
                'service_odometer_km' => 120000,
                'service_date' => now()->subDays(43)->toDateString(),
                'interval_km' => 10000,
                'supplier' => 'Oficina Central',
                'invoice_number' => 'NF-123',
                'cost_amount' => 890.50,
                'work_order_number' => 'OS-001',
            ]);

        $response->assertCreated()
            ->assertJsonPath('record.vehicle_id', $vehicle->id)
            ->assertJsonPath('record.next_service_odometer_km', 130000)
            ->assertJsonPath('projection.vehicle_plate', 'OIL1234')
            ->assertJsonPath('projection.remaining_km', 900)
            ->assertJsonPath('projection.severity', 'MEDIO');

        $this->assertDatabaseHas('oil_service_records', [
            'vehicle_id' => $vehicle->id,
            'service_odometer_km' => 120000,
            'next_service_odometer_km' => 130000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'oil_service_records',
            'entity_id' => $response->json('record.id'),
            'actor_id' => $user->id,
            'origin' => 'api.v1.oil-services.store',
        ]);
    }

    public function test_oil_service_in_preventive_range_creates_alert_and_mission_control_counts_it(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage', 'mission-control.view']);
        $vehicle = $this->vehicleFor($user, currentOdometerKm: 129100);
        $token = $this->tokenFor($user);

        $this->withToken($token)
            ->postJson('/api/v1/oil-services', [
                'vehicle_id' => $vehicle->id,
                'service_odometer_km' => 120000,
                'service_date' => now()->subDays(43)->toDateString(),
                'interval_km' => 10000,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('alerts', [
            'company_id' => $user->company_id,
            'entity' => 'vehicles',
            'entity_id' => $vehicle->id,
            'rule' => 'oil-service-projection',
            'severity' => 'MEDIO',
            'status' => 'ABERTO',
        ]);

        $this->assertDatabaseHas('insights', [
            'company_id' => $user->company_id,
            'entity' => 'vehicles',
            'entity_id' => $vehicle->id,
            'type' => 'OIL_SERVICE_FORECAST',
            'status' => 'OPEN',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('alerts.open', 1)
            ->assertJsonPath('alerts.medium', 1)
            ->assertJsonPath('insights.open', 1);

        $alertId = $this->withToken($token)
            ->getJson('/api/v1/alerts?status=ABERTO')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0.id');

        $this->withToken($token)
            ->patchJson('/api/v1/alerts/'.$alertId, ['status' => 'VISUALIZADO'])
            ->assertOk()
            ->assertJsonPath('status', 'VISUALIZADO');

        $insightId = $this->withToken($token)
            ->getJson('/api/v1/insights?status=OPEN&type=OIL_SERVICE_FORECAST')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.priority', 60)
            ->json('data.0.id');

        $this->withToken($token)
            ->patchJson('/api/v1/insights/'.$insightId, ['status' => 'ACKNOWLEDGED'])
            ->assertOk()
            ->assertJsonPath('status', 'ACKNOWLEDGED');
    }

    public function test_oil_service_rejects_odometer_above_vehicle_current_odometer(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage']);
        $vehicle = $this->vehicleFor($user, currentOdometerKm: 1000);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/oil-services', [
                'vehicle_id' => $vehicle->id,
                'service_odometer_km' => 1500,
                'service_date' => now()->toDateString(),
                'interval_km' => 10000,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Vehicle odometer cannot be lower than oil service odometer.');
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

    private function vehicleFor(User $user, int $currentOdometerKm = 120000): Vehicle
    {
        return Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'OIL1234',
            'current_odometer_km' => $currentOdometerKm,
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
