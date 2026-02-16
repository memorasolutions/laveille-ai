<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_admin_panel',
            'manage_users',
            'manage_roles',
            'manage_settings',
            'view_logs',
            'manage_media',
            'manage_notifications',
            'manage_webhooks',
            'manage_api',
            'manage_seo',
            'manage_themes',
            'manage_backups',
            'view_health',
            'view_telescope',
            'view_horizon',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_admin_panel',
            'manage_users',
            'manage_settings',
            'view_logs',
            'manage_media',
            'manage_notifications',
            'manage_seo',
            'manage_themes',
            'view_health',
        ]);

        Role::firstOrCreate(['name' => 'user']);
    }
}
