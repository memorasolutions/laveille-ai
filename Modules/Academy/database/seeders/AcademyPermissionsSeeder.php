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

    /**
     * Permissions GLOBALES d'un formateur (rôle instructor).
     * Volontairement SANS academy.manage ni academy.courses.delete : la gestion
     * réelle est scopée au cours par les policies (course_roles), pas par ces
     * permissions globales. Elles servent seulement de « porte d'entrée » à
     * l'espace de gestion ; l'autorisation par-cours reste rôle+ownership.
     *
     * @var string[]
     */
    private array $instructorPermissions = [
        'academy.view',
        'academy.enroll',
        'academy.courses.create',
        'academy.courses.update',
        'academy.courses.publish',
        'academy.lessons.manage',
        'academy.enrollments.manage',
    ];

    /**
     * Permissions GLOBALES d'un étudiant (rôle student) : voir + s'inscrire, rien de plus.
     *
     * @var string[]
     */
    private array $studentPermissions = [
        'academy.view',
        'academy.enroll',
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

        // Nouveaux rôles Spatie scopés à l'Académie (idempotent : firstOrCreate).
        // givePermissionTo est additif : ne retire jamais de droits existants.
        $instructor = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        $instructor->givePermissionTo($this->instructorPermissions);

        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->givePermissionTo($this->studentPermissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
