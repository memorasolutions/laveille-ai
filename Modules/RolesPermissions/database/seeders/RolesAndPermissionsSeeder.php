<?php

declare(strict_types=1);

namespace Modules\RolesPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions de base
        $permissions = [
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
            // Roles
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            // Settings
            'settings.view', 'settings.edit',
            // Media
            'media.view', 'media.upload', 'media.delete',
            // Logs
            'logs.view',
            // Backups
            'backups.view', 'backups.create', 'backups.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super admin - a toutes les permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - tout sauf gestion des rôles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(
            Permission::whereNotIn('name', ['roles.create', 'roles.edit', 'roles.delete'])->get()
        );

        // User - permissions de base
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo(['users.view', 'media.view', 'media.upload']);
    }
}
