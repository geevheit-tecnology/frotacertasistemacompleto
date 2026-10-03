<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ComplianceOperationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_compliance_operations_calendar_correlates_expiring_document_with_future_trip(): void
    {
        $user = $this->createUserWithPermissions(['documents.manage', 'operations.manage', 'integrations.manage', 'financial.manage', 'mission-control.view']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'plate' => 'DOC1234',
            'status' => 'ACTIVE',
            'current_odometer_km' => 10000,
        ]);

        VehicleDocument::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'vehicle_id' => $vehicle->id,
            'document_type' => 'Licenciamento',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->addDays(18),
            'status' => 'ACTIVE',
            'responsible_user_id' => $user->id,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/trips', [
                'vehicle_id' => $vehicle->id,
                'origin' => 'Sao Paulo',
                'destination' => 'Curitiba',
                'starts_at' => now()->addDays(25)->toISOString(),
                'status' => 'SCHEDULED',
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/insurance-policies', [
                'vehicle_id' => $vehicle->id,
                'policy_number' => 'SEG-001',
                'provider' => 'Seguradora Teste',
                'expires_at' => now()->addDays(45)->toDateString(),
                'premium_amount' => 1500,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/contracts', [
                'number' => 'CTR-001',
                'type' => 'Oficina',
                'ends_at' => now()->addDays(60)->toDateString(),
                'amount' => 2500,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/fines', [
                'vehicle_id' => $vehicle->id,
                'notice_number' => 'AIT-001',
                'occurred_at' => now()->subDay()->toDateString(),
                'due_at' => now()->addDays(10)->toDateString(),
                'amount' => 195.23,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/telematics-events', [
                'vehicle_id' => $vehicle->id,
                'recorded_at' => now()->toISOString(),
                'odometer_km' => 10100,
                'speed_kmh' => 72,
                'source' => 'API',
            ])
            ->assertCreated();

        $this->withToken($token)
            ->getJson('/api/v1/compliance-calendar?days=90')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'VEHICLE_DOCUMENT')
            ->assertJsonPath('data.0.future_trip_after_due', true)
            ->assertJsonPath('data.0.risk', 'CRITICO');

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('compliance.vehicle_documents_next_90_days', 1)
            ->assertJsonPath('compliance.insurance_next_90_days', 1)
            ->assertJsonPath('compliance.contracts_next_90_days', 1)
            ->assertJsonPath('operations.scheduled_trips', 1)
            ->assertJsonPath('operations.telematics_events', 1);
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
