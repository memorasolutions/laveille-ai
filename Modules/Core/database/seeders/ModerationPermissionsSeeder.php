<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ModerationPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'moderate_reviews',
            'moderate_discussions',
            'moderate_resources',
            'moderate_suggestions',
            'moderate_reports',
            'moderate_acronyms',
            'moderate_ideas',
            'view_moderation_history',
            'ban_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdminRole = Role::where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permissions);
        }
    }
}
