<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\FinancialTransaction;
use App\Models\FuelRecord;
use App\Models\Group;
use App\Models\Outbox;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseNineAuditReportsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_report_export_executive_report_and_ready_observability(): void
    {
        $user = $this->createUserWithPermissions(['audit.view', 'reports.view']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'REP1234',
            'status' => 'ACTIVE',
            'current_odometer_km' => 12000,
        ]);
        AuditLog::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'actor_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'entity' => 'vehicles',
            'entity_id' => $vehicle->id,
            'action' => 'created',
            'origin' => 'test',
        ]);
        FuelRecord::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'vehicle_id' => $vehicle->id,
            'fuel_type' => 'DIESEL',
            'quantity_liters' => 50,
            'unit_price' => 6,
            'total_amount' => 300,
            'odometer_km' => 12100,
            'filled_at' => now(),
        ]);
        FinancialTransaction::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'vehicle_id' => $vehicle->id,
            'type' => 'PAYABLE',
            'category' => 'FUEL',
            'amount' => 300,
            'status' => 'OPEN',
            'origin' => 'test',
        ]);
        Outbox::create([
            'event_name' => 'test.event.v1',
            'aggregate_type' => 'tests',
            'aggregate_id' => (string) Str::uuid(),
            'payload' => [],
            'idempotency_key' => (string) Str::uuid(),
            'status' => 'PENDING',
            'available_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/audit-logs?entity=vehicles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entity', 'vehicles');

        $this->withToken($token)
            ->get('/api/v1/audit-logs/export.csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->withToken($token)
            ->getJson('/api/v1/reports/executive')
            ->assertOk()
            ->assertJsonPath('fleet.total', 1)
            ->assertJsonPath('costs.open_payables', '300.00')
            ->assertJsonPath('costs.fuel_amount', '300.00');

        $this->getJson('/api/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('database', 'ok')
            ->assertJsonPath('outbox_pending', 1);
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
