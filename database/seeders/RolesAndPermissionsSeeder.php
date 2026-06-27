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
        $this->migrateDeprecatedPermissions();
        $this->createRoles();
        $this->createSuperAdminUser();
    }

    private function createPermissions(): void
    {
        $corePermissions = [
            'dashboard.view' => 'View dashboard',
            'admin.users.view' => 'View users',
            'admin.users.create' => 'Create users',
            'admin.users.edit' => 'Edit users',
            'admin.users.delete' => 'Delete users',
            'admin.roles.view' => 'View roles and permissions',
            'admin.roles.create' => 'Create roles',
            'admin.roles.edit' => 'Edit role permissions',
            'admin.roles.delete' => 'Delete roles',
            'admin.settings.view' => 'View system settings',
            'admin.settings.edit' => 'Edit system settings',
            'admin.audit.view' => 'View centralized audit logs',
            'admin.audit.export' => 'Export audit logs',
            'admin.audit.delete' => 'Delete audit logs',
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
        $administrator->givePermissionTo(
            Permission::whereNotIn('name', [
                'admin.roles.view',
                'admin.roles.create',
                'admin.roles.edit',
                'admin.roles.delete',
                'admin.audit.view',
                'admin.audit.export',
                'admin.audit.delete',
            ])->get()
        );

        $officer = Role::findOrCreate('Officer', 'web');
        $officer->givePermissionTo([
            'dashboard.view',
            'cases.view',
            'cases.create',
            'cases.edit',
            'cases.notes.add',
            'cases.notes.edit',
            'cases.notes.delete',
            'cases.documents.upload',
            'cases.documents.delete',
            'cases.documents.restore',
            'cases.documents.recycle_bin',
            'cases.import.view',
            'cases.import.upload',
            'cases.import.bulk',
            'cases.import.execute',
            'cases.import.rollback',
            'cases.import.reset',
            'reports.view', 'reports.export',
        ]);

        $viewer = Role::findOrCreate('Viewer', 'web');
        $viewer->givePermissionTo(['dashboard.view', 'cases.view', 'reports.view']);
    }

    private function migrateDeprecatedPermissions(): void
    {
        $map = [
            'cases.notes.manage' => ['cases.notes.add', 'cases.notes.edit', 'cases.notes.delete'],
            'cases.documents.manage' => ['cases.documents.upload', 'cases.documents.delete', 'cases.documents.restore'],
            'cases.import' => ['cases.import.view', 'cases.import.upload', 'cases.import.bulk', 'cases.import.execute', 'cases.import.rollback', 'cases.import.reset'],
            'admin.users' => ['admin.users.view', 'admin.users.create', 'admin.users.edit', 'admin.users.delete'],
            'admin.roles' => ['admin.roles.view', 'admin.roles.create', 'admin.roles.edit', 'admin.roles.delete'],
            'admin.settings' => ['admin.settings.view', 'admin.settings.edit'],
        ];

        foreach ($map as $oldPermission => $newPermissions) {
            $old = Permission::where('name', $oldPermission)->where('guard_name', 'web')->first();
            if (! $old) {
                continue;
            }

            $roles = Role::permission($oldPermission)->get();
            foreach ($roles as $role) {
                $role->givePermissionTo($newPermissions);
                $role->revokePermissionTo($oldPermission);
            }

            $old->delete();
        }
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
