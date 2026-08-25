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

class PhaseSevenFinancialFuelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_fuel_record_creates_financial_transaction_and_updates_vehicle_costs(): void
    {
        $user = $this->createUserWithPermissions(['financial.manage', 'mission-control.view']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'FIN1234',
            'current_odometer_km' => 10000,
        ]);

        $costCenterId = $this->withToken($token)->postJson('/api/v1/cost-centers', [
            'name' => 'Operacao Rodoviaria',
            'code' => 'OP-001',
        ])->assertCreated()->json('id');

        $this->withToken($token)->postJson('/api/v1/fuel-records', [
            'vehicle_id' => $vehicle->id,
            'cost_center_id' => $costCenterId,
            'fuel_type' => 'DIESEL',
            'quantity_liters' => 100,
            'unit_price' => 6.25,
            'odometer_km' => 10500,
        ])->assertCreated()
            ->assertJsonPath('total_amount', '625.00');

        $this->assertDatabaseHas('financial_transactions', [
            'vehicle_id' => $vehicle->id,
            'category' => 'FUEL',
            'amount' => 625,
            'status' => 'OPEN',
            'origin' => 'fuel-record',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'current_odometer_km' => 10500,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('fuel.records', 1)
            ->assertJsonPath('fuel.total_amount', '625.00')
            ->assertJsonPath('financial.open_payables', '625.00');
    }

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
