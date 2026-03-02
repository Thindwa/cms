<?php

namespace Database\Seeders;

use App\Core\Support\ModuleRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->createPermissions();
        $this->createRoles();
        $this->createSuperAdminUser();
    }

    private function createPermissions(): void
    {
        $corePermissions = [
            'dashboard.view' => 'View dashboard',
            'admin.users' => 'Manage users',
            'admin.roles' => 'Manage roles and permissions',
            'admin.settings' => 'Manage system settings',
        ];

        foreach ($corePermissions as $name => $guardName) {
            Permission::findOrCreate($name, 'web');
        }

        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);
        foreach ($registry->allPermissions() as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }
    }

    private function createRoles(): void
    {
        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        $administrator = Role::findOrCreate('Administrator', 'web');
        $administrator->givePermissionTo(Permission::whereNot('name', 'admin.roles')->get());

        $officer = Role::findOrCreate('Officer', 'web');
        $officer->givePermissionTo([
            'dashboard.view', 'cases.view', 'cases.create', 'cases.edit', 'cases.assign',
            'reports.view', 'reports.export',
        ]);

        $viewer = Role::findOrCreate('Viewer', 'web');
        $viewer->givePermissionTo(['dashboard.view', 'cases.view', 'reports.view']);
    }

    private function createSuperAdminUser(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]
        );
        $user->update(['password' => bcrypt('password')]);
        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }
    }
}
