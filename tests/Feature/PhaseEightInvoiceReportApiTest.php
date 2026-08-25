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

class PhaseEightInvoiceReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_creates_financial_transaction_flags_and_vehicle_cost_report(): void
    {
        $user = $this->createUserWithPermissions(['financial.manage', 'mission-control.view']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'NFE1234',
            'current_odometer_km' => 10000,
        ]);

        $invoiceId = $this->withToken($token)
            ->postJson('/api/v1/invoices', [
                'vehicle_id' => $vehicle->id,
                'number' => 'NF-001',
                'series' => '1',
                'issued_at' => now()->toDateString(),
                'amount' => 1200,
                'category' => 'MAINTENANCE',
            ])
            ->assertCreated()
            ->assertJsonPath('flags.0', 'MISSING_COST_CENTER')
            ->json('id');

        $this->assertDatabaseHas('financial_transactions', [
            'vehicle_id' => $vehicle->id,
            'category' => 'MAINTENANCE',
            'amount' => 1200,
            'origin' => 'invoice',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/reports/vehicles/'.$vehicle->id.'/costs')
            ->assertOk()
            ->assertJsonPath('vehicle_id', $vehicle->id)
            ->assertJsonPath('invoice_cost', 1200)
            ->assertJsonPath('total_cost', 2400)
            ->assertJsonPath('cost_per_km', 0.24);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('financial.invoices_pending', 1);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'invoices',
            'entity_id' => $invoiceId,
            'origin' => 'api.v1.invoices.store',
        ]);
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
