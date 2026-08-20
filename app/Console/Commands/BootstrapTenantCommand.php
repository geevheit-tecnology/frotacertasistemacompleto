<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BootstrapTenantCommand extends Command
{
    protected $signature = 'fleet:bootstrap
        {--group=Grupo Principal}
        {--company=Empresa Principal}
        {--name=Administrador}
        {--email=admin@example.com}
        {--password=ChangeMe123!}';

    protected $description = 'Creates the first group, company, admin user, roles and permissions.';

    public function handle(): int
    {
        $group = Group::firstOrCreate(['name' => (string) $this->option('group')]);
        $company = Company::firstOrCreate(
            ['group_id' => $group->id, 'legal_name' => (string) $this->option('company')],
            ['trade_name' => (string) $this->option('company')]
        );

        $permissions = collect([
            'administration.manage',
            'fleet.manage',
            'documents.manage',
            'mission-control.view',
        ])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]));

        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $role->permissions()->sync($permissions->pluck('id')->all());

        $user = User::firstOrCreate(
            ['email' => (string) $this->option('email')],
            [
                'group_id' => $group->id,
                'company_id' => $company->id,
                'name' => (string) $this->option('name'),
                'password' => Hash::make((string) $this->option('password')),
                'status' => 'ACTIVE',
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->info('Bootstrap completed.');
        $this->line('Group: '.$group->id);
        $this->line('Company: '.$company->id);
        $this->line('Admin: '.$user->email);

        return self::SUCCESS;
    }
}
