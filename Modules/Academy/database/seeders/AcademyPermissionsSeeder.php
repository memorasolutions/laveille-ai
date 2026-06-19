<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AcademyPermissionsSeeder extends Seeder
{
    /** @var string[] */
    private array $permissions = [
        'academy.view',
        'academy.enroll',
        'academy.manage',
        'academy.courses.create',
        'academy.courses.update',
        'academy.courses.delete',
        'academy.courses.publish',
        'academy.lessons.manage',
        'academy.enrollments.manage',
        'academy.certificates.issue',
        'academy.reports.view',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Attacher les permissions aux rôles existants uniquement (additif, pas de syncPermissions)
        foreach (['super_admin', 'admin'] as $roleName) {
            try {
                $role = Role::findByName($roleName, 'web');
                $role->givePermissionTo($this->permissions);
            } catch (RoleDoesNotExist) {
                // Rôle absent → skip silencieux
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
