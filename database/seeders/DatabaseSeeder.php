<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect([
            'administration.manage',
            'fleet.manage',
            'documents.manage',
            'mission-control.view',
        ])->mapWithKeys(fn (string $name) => [$name => Permission::firstOrCreate(['name' => $name])]);

        $admin = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin->permissions()->sync($permissions->pluck('id')->all());
    }
}
