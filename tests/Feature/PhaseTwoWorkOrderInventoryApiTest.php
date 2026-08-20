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

class PhaseTwoWorkOrderInventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_consumes_inventory_and_recalculates_costs(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'OS1234',
            'current_odometer_km' => 45000,
        ]);

        $supplierId = $this->withToken($token)->postJson('/api/v1/suppliers', [
            'name' => 'Oficina Central',
            'type' => 'WORKSHOP',
        ])->assertCreated()->json('id');

        $partId = $this->withToken($token)->postJson('/api/v1/inventory-parts', [
            'sku' => 'FLT-001',
            'name' => 'Filtro de oleo',
            'quantity_on_hand' => 10,
            'minimum_quantity' => 2,
            'average_cost' => 45.50,
        ])->assertCreated()->json('id');

        $workOrderId = $this->withToken($token)->postJson('/api/v1/work-orders', [
            'vehicle_id' => $vehicle->id,
            'supplier_id' => $supplierId,
            'number' => 'OS-0001',
            'type' => 'PREVENTIVE',
            'description' => 'Troca preventiva de oleo e filtro.',
            'opened_at' => now()->toDateString(),
            'labor_cost' => 120,
        ])->assertCreated()->assertJsonPath('total_cost', '120.00')->json('id');

        $this->withToken($token)->postJson('/api/v1/work-orders/'.$workOrderId.'/parts', [
            'inventory_part_id' => $partId,
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('parts_cost', '91.00')
            ->assertJsonPath('total_cost', '211.00')
            ->assertJsonPath('parts.0.quantity', '2.000');

        $this->assertDatabaseHas('inventory_parts', [
            'id' => $partId,
            'quantity_on_hand' => 8,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $partId,
            'work_order_id' => $workOrderId,
            'type' => 'OUT',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'work_orders',
            'entity_id' => $workOrderId,
            'action' => 'updated',
            'origin' => 'api.v1.work-orders.parts.store',
        ]);
    }

    public function test_work_order_rejects_inventory_consumption_above_available_stock(): void
    {
        $user = $this->createUserWithPermissions(['maintenance.manage']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'OS9999',
            'current_odometer_km' => 45000,
        ]);
        $partId = $this->withToken($token)->postJson('/api/v1/inventory-parts', [
            'name' => 'Correia',
            'quantity_on_hand' => 1,
            'average_cost' => 100,
        ])->assertCreated()->json('id');
        $workOrderId = $this->withToken($token)->postJson('/api/v1/work-orders', [
            'vehicle_id' => $vehicle->id,
            'number' => 'OS-0002',
            'description' => 'Corretiva.',
            'opened_at' => now()->toDateString(),
        ])->assertCreated()->json('id');

        $this->withToken($token)->postJson('/api/v1/work-orders/'.$workOrderId.'/parts', [
            'inventory_part_id' => $partId,
            'quantity' => 2,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Insufficient inventory quantity.');
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
