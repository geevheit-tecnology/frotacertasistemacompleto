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

class PhaseSixTireApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tire_inspection_generates_preventive_alert_and_insight(): void
    {
        $user = $this->createUserWithPermissions(['tires.manage', 'mission-control.view']);
        $token = $this->tokenFor($user);
        $vehicle = Vehicle::create([
            'group_id' => $user->group_id,
            'company_id' => $user->company_id,
            'plate' => 'PNE1234',
            'current_odometer_km' => 80000,
        ]);

        $tireId = $this->withToken($token)->postJson('/api/v1/tires', [
            'fire_number' => 'FOGO-001',
            'manufacturer' => 'Michelin',
            'size' => '295/80R22.5',
            'vehicle_id' => $vehicle->id,
            'position' => 'DIANTEIRO_ESQUERDO',
            'status' => 'INSTALADO',
        ])->assertCreated()->json('id');

        $this->withToken($token)->postJson('/api/v1/tire-inspections', [
            'tire_id' => $tireId,
            'tread_depth_mm' => 1.5,
            'pressure_psi' => 92,
            'wear_pattern' => 'NORMAL',
            'photo_paths' => ['tires/fogo-001.jpg'],
            'visual_confidence' => 0.86,
        ])->assertCreated()->assertJsonPath('tread_depth_mm', '1.50');

        $this->assertDatabaseHas('alerts', [
            'entity' => 'tires',
            'entity_id' => $tireId,
            'rule' => 'tire-preventive-inspection',
            'severity' => 'CRITICO',
            'status' => 'ABERTO',
        ]);
        $this->assertDatabaseHas('insights', [
            'entity' => 'tires',
            'entity_id' => $tireId,
            'type' => 'PNEU',
            'status' => 'OPEN',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/mission-control')
            ->assertOk()
            ->assertJsonPath('tires.total', 1)
            ->assertJsonPath('tires.installed', 1)
            ->assertJsonPath('tires.critical_tread', 1);
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
