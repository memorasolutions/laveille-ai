<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Enregistre la permission Spatie « sso.manage » (gestion des configurations
 * SSO/SCIM par organisation) — même pattern que
 * Modules\Academy\Database\Seeders\AcademyPermissionsSeeder. Additif
 * (givePermissionTo n'écrase jamais un rôle existant), idempotent
 * (firstOrCreate). Non exécuté automatiquement : à lancer explicitement
 * (php artisan db:seed --class=Modules\\Sso\\Database\\Seeders\\SsoPermissionsSeeder)
 * lors de l'activation réelle du module en production.
 */

declare(strict_types=1);

namespace Modules\Sso\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SsoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'sso.manage', 'guard_name' => 'web']);

        foreach (['super_admin', 'admin'] as $roleName) {
            try {
                $role = Role::findByName($roleName, 'web');
                $role->givePermissionTo('sso.manage');
            } catch (RoleDoesNotExist) {
                // Rôle absent → skip silencieux
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
