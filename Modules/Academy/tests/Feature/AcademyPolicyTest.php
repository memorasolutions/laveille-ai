<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Fondation de sécurité Academy (modèle « rôle + ownership », OWASP A01).
 * Prouve que l'autorisation de GESTION est SERVEUR et SCOPÉE au cours :
 *  - admin gère tous les cours ;
 *  - formateur gère SES cours seulement (test clé anti-escalade) ;
 *  - étudiant ne gère rien (peut s'inscrire) ;
 *  - user sans rôle ne gère rien.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Sauter si le module n'est pas activé (ex. CI sans Academy)
    if (! \Nwidart\Modules\Facades\Module::find('Academy')?->isEnabled()) {
        test()->markTestSkipped('Module Academy désactivé — tests skipped.');
    }

    // Rôles de base (super_admin, admin, user...) puis permissions + rôles Academy
    // (instructor, student). Les deux seeders sont idempotents.
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    // Admin : super_admin reçoit academy.manage via le seeder Academy.
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    if (! $this->admin->can('academy.manage')) {
        $this->admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    // Cours A et cours B (deux cours distincts pour prouver le scoping par-cours).
    $this->courseA = makeCourse('cours-a', 'Cours A');
    $this->courseB = makeCourse('cours-b', 'Cours B');
});

/** Helper local : crée un cours gratuit publié minimal. */
function makeCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. ADMIN — gère N'IMPORTE QUEL cours
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut update/publish/delete/manageStructure/manageEnrollments tout cours', function (): void {
    foreach ([$this->courseA, $this->courseB] as $course) {
        expect($this->admin->can('update', $course))->toBeTrue();
        expect($this->admin->can('publish', $course))->toBeTrue();
        expect($this->admin->can('delete', $course))->toBeTrue();
        expect($this->admin->can('manageStructure', $course))->toBeTrue();
        expect($this->admin->can('manageEnrollments', $course))->toBeTrue();
        expect($this->admin->can('manageRoles', $course))->toBeTrue();
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. FORMATEUR — gère SON cours (A), PAS celui d'un autre (B) → anti-escalade
// ─────────────────────────────────────────────────────────────────────────────

test('formateur (owner du cours A) peut gérer A', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');
    CourseRole::create(['course_id' => $this->courseA->id, 'user_id' => $formateur->id, 'role' => 'owner']);

    expect($formateur->can('update', $this->courseA))->toBeTrue();
    expect($formateur->can('publish', $this->courseA))->toBeTrue();
    expect($formateur->can('manageStructure', $this->courseA))->toBeTrue();
    expect($formateur->can('manageEnrollments', $this->courseA))->toBeTrue();
    expect($formateur->can('delete', $this->courseA))->toBeTrue();      // owner peut supprimer SON cours
    expect($formateur->can('manageRoles', $this->courseA))->toBeTrue(); // owner gère les rôles de SON cours
});

test('formateur (instructor du cours A) peut gérer A mais ne le supprime pas (pas owner)', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');
    CourseRole::create(['course_id' => $this->courseA->id, 'user_id' => $formateur->id, 'role' => 'instructor']);

    expect($formateur->can('update', $this->courseA))->toBeTrue();
    expect($formateur->can('publish', $this->courseA))->toBeTrue();
    expect($formateur->can('manageStructure', $this->courseA))->toBeTrue();
    expect($formateur->can('manageEnrollments', $this->courseA))->toBeTrue();
    expect($formateur->can('delete', $this->courseA))->toBeFalse();      // delete réservé à owner/admin
    expect($formateur->can('manageRoles', $this->courseA))->toBeFalse(); // manageRoles réservé à owner/admin
});

test('ANTI-ESCALADE : formateur du cours A ne peut RIEN gérer du cours B', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');
    CourseRole::create(['course_id' => $this->courseA->id, 'user_id' => $formateur->id, 'role' => 'owner']);

    // Il a la permission globale academy.courses.update/publish, mais N'EST PAS sur le cours B.
    expect($formateur->can('update', $this->courseB))->toBeFalse();
    expect($formateur->can('publish', $this->courseB))->toBeFalse();
    expect($formateur->can('delete', $this->courseB))->toBeFalse();
    expect($formateur->can('manageStructure', $this->courseB))->toBeFalse();
    expect($formateur->can('manageEnrollments', $this->courseB))->toBeFalse();
    expect($formateur->can('manageRoles', $this->courseB))->toBeFalse();
});

test('formateur peut créer un cours (deviendra owner)', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');

    expect($formateur->can('create', Course::class))->toBeTrue();
    expect($formateur->can('viewAny', Course::class))->toBeTrue();
});

test('formateur sans course_role ne peut pas supprimer un cours', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');

    expect($formateur->can('delete', $this->courseA))->toBeFalse();
    expect($formateur->can('delete', $this->courseB))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ÉTUDIANT — ne gère rien ; peut s'inscrire à un cours gratuit publié
// ─────────────────────────────────────────────────────────────────────────────

test('étudiant ne peut rien gérer', function (): void {
    $etudiant = User::factory()->create();
    $etudiant->assignRole('student');

    expect($etudiant->can('update', $this->courseA))->toBeFalse();
    expect($etudiant->can('publish', $this->courseA))->toBeFalse();
    expect($etudiant->can('delete', $this->courseA))->toBeFalse();
    expect($etudiant->can('manageStructure', $this->courseA))->toBeFalse();
    expect($etudiant->can('manageEnrollments', $this->courseA))->toBeFalse();
    expect($etudiant->can('manageRoles', $this->courseA))->toBeFalse();
    expect($etudiant->can('create', Course::class))->toBeFalse();
    expect($etudiant->can('viewAny', Course::class))->toBeFalse();
});

test('étudiant peut s\'inscrire à un cours gratuit publié', function (): void {
    $etudiant = User::factory()->create();
    $etudiant->assignRole('student');

    expect($etudiant->can('enroll', $this->courseA))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. USER SANS RÔLE — ne gère rien
// ─────────────────────────────────────────────────────────────────────────────

test('user sans rôle ne peut rien gérer', function (): void {
    $user = User::factory()->create();

    expect($user->can('update', $this->courseA))->toBeFalse();
    expect($user->can('publish', $this->courseA))->toBeFalse();
    expect($user->can('delete', $this->courseA))->toBeFalse();
    expect($user->can('manageStructure', $this->courseA))->toBeFalse();
    expect($user->can('manageEnrollments', $this->courseA))->toBeFalse();
    expect($user->can('manageRoles', $this->courseA))->toBeFalse();
    expect($user->can('create', Course::class))->toBeFalse();
    expect($user->can('viewAny', Course::class))->toBeFalse();
});
