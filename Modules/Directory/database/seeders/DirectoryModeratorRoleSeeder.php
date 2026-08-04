<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DirectoryModeratorRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'moderate_tools',
            'approve_tools',
            'reject_tools',
            // Round 1541 (2026-08-03) : sans view_admin_panel, EnsureIsAdmin bloquait
            // directory_moderator AVANT même que 'can:moderate_tools' ne soit évalué -
            // le rôle ne pouvait jamais atteindre /admin/directory/* malgré ses permissions.
            'view_admin_panel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'directory_moderator',
            'guard_name' => 'web',
        ]);
        $role->update(['level' => 50]);

        $role->syncPermissions($permissions);

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }
}
