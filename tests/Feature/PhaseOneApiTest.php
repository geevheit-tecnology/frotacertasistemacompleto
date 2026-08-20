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

class PhaseOneApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_access_me(): void
    {
        $user = $this->createUserWithPermissions(['fleet.manage']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);

        $this->withToken($login->json('access_token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_permission_is_required_for_vehicle_management(): void
    {
        $user = $this->createUserWithPermissions(['mission-control.view']);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/vehicles', ['plate' => 'ABC1234'])
            ->assertForbidden();
    }

    public function test_vehicle_creation_uses_authenticated_scope_and_writes_audit(): void
    {
        $user = $this->createUserWithPermissions(['fleet.manage']);

        $response = $this->withToken($this->tokenFor($user))->postJson('/api/v1/vehicles', [
            'plate' => 'ABC1234',
            'fleet_number' => '001',
            'current_odometer_km' => 1200,
        ]);

        $response->assertCreated()
            ->assertJsonPath('plate', 'ABC1234')
            ->assertJsonPath('group_id', $user->group_id)
            ->assertJsonPath('company_id', $user->company_id);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'vehicles',
            'entity_id' => $response->json('id'),
            'actor_id' => $user->id,
            'origin' => 'api.v1.vehicles.store',
        ]);
    }

    public function test_vehicle_listing_is_limited_to_authenticated_company(): void
    {
        $user = $this->createUserWithPermissions(['fleet.manage']);
        $otherCompany = Company::create([
            'group_id' => $user->group_id,
            'legal_name' => 'Outra Empresa',
        ]);

        Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'AAA1111',
        ]);

        Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $otherCompany->id,
            'plate' => 'BBB2222',
        ]);

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plate', 'AAA1111');
    }

    public function test_vehicle_can_be_updated_and_deleted_with_audit(): void
    {
        $user = $this->createUserWithPermissions(['fleet.manage']);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'AAA1111',
        ]);

        $token = $this->tokenFor($user);

        $this->withToken($token)
            ->patchJson('/api/v1/vehicles/'.$vehicle->id, ['status' => 'MAINTENANCE'])
            ->assertOk()
            ->assertJsonPath('status', 'MAINTENANCE');

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'vehicles',
            'entity_id' => $vehicle->id,
            'action' => 'updated',
        ]);

        $this->withToken($token)
            ->deleteJson('/api/v1/vehicles/'.$vehicle->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'vehicles',
            'entity_id' => $vehicle->id,
            'action' => 'deleted',
        ]);
    }

    public function test_bootstrap_command_creates_admin_role_and_user(): void
    {
        $this->artisan('fleet:bootstrap', [
            '--email' => 'admin@frotacerta.test',
            '--password' => 'StrongPass123!',
        ])->assertSuccessful();

        $admin = User::query()
            ->where('email', 'admin@frotacerta.test')
            ->with('roles.permissions')
            ->firstOrFail();

        $this->assertTrue($admin->hasPermission('administration.manage'));
        $this->assertTrue($admin->hasPermission('fleet.manage'));
        $this->assertTrue($admin->hasPermission('documents.manage'));
        $this->assertTrue($admin->hasPermission('mission-control.view'));
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
