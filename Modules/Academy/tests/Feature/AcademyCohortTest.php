<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Cohortes / groupes d'apprenants (CourseRoster, Phase C / C2).
 *
 * Prouve que CHAQUE mutation est gardée par une autorisation SERVEUR (OWASP A01,
 * gate 'manageEnrollments' = admin OU owner/instructor du cours) et que :
 *  - owner/admin crée une cohorte, y affecte des inscrits, filtre, en retire ;
 *  - étudiant / user lambda → 403 (création/affectation) ;
 *  - ANTI-IDOR : affecter à une cohorte d'un AUTRE cours (id forgé) → refusé,
 *    rien écrit ;
 *  - ANTI-IDOR : affecter un user NON inscrit au cours → refusé ;
 *  - supprimer une cohorte retire les liens pivot SANS toucher aux inscriptions.
 *
 * Fichier AUTONOME : helpers locaux préfixés Cohort (aucune dépendance ni collision
 * avec les autres fichiers de tests Academy ; convention du module).
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseRoster;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = makeCohortCourse('cohort-a', 'Cours A');
    $this->courseB = makeCohortCourse('cohort-b', 'Cours B');
});

/** Helper local autonome : cours gratuit en brouillon (préfixe Cohort, sans collision inter-fichiers). */
function makeCohortCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);
}

/** Helper local : admin academy.manage. */
function makeCohortAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

/** Helper local : formateur avec un rôle de cours donné (owner par défaut). */
function makeCohortRole(Course $course, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $user->id,
        'role'      => $role,
    ]);

    return $user;
}

/** Helper local : un étudiant (compte existant, sans rôle de cours). */
function makeCohortStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

/** Helper local : inscrit (actif) un nouvel étudiant à un cours et le retourne. */
function makeCohortEnrolled(Course $course): User
{
    $student = makeCohortStudent();
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $student->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $student;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. PARCOURS COMPLET - owner crée, affecte 2 inscrits, filtre, retire 1
// ─────────────────────────────────────────────────────────────────────────────

test('owner crée une cohorte, y affecte 2 inscrits, filtre puis en retire 1', function (): void {
    $owner = makeCohortRole($this->courseA, 'owner');
    $s1    = makeCohortEnrolled($this->courseA);
    $s2    = makeCohortEnrolled($this->courseA);

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    // Crée la cohorte
    $component->set('cohortName', 'Groupe A')
        ->call('createCohort')
        ->assertHasNoErrors();

    $cohort = Cohort::where('course_id', $this->courseA->id)->firstOrFail();
    expect($cohort->name)->toBe('Groupe A');

    // Affecte les 2 inscrits
    $component->set('assignCohortId', $cohort->id)
        ->set('assignEnrollmentUserId', $s1->id)
        ->call('assignToCohort')
        ->assertHasNoErrors();

    $component->set('assignCohortId', $cohort->id)
        ->set('assignEnrollmentUserId', $s2->id)
        ->call('assignToCohort')
        ->assertHasNoErrors();

    expect($cohort->fresh()->members()->count())->toBe(2);

    // Filtre → voit les 2
    $component->set('cohortFilter', $cohort->id);
    expect($component->instance()->enrollments()->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$s1->id, $s2->id])->sort()->values()->all());

    // Retire 1 → reste 1
    $component->call('removeFromCohort', $cohort->id, $s1->id)
        ->assertHasNoErrors();

    expect($cohort->fresh()->members()->count())->toBe(1);
    expect($cohort->fresh()->members()->first()->id)->toBe($s2->id);
});

test('admin peut créer une cohorte sur n\'importe quel cours', function (): void {
    Livewire::actingAs(makeCohortAdmin())
        ->test(CourseRoster::class, ['course' => $this->courseB])
        ->set('cohortName', 'Session B')
        ->call('createCohort')
        ->assertHasNoErrors();

    expect(Cohort::where('course_id', $this->courseB->id)->where('name', 'Session B')->exists())->toBeTrue();
});

test('owner peut renommer une cohorte de SON cours', function (): void {
    $owner  = makeCohortRole($this->courseA, 'owner');
    $cohort = Cohort::create(['course_id' => $this->courseA->id, 'name' => 'Ancien']);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->call('startRenameCohort', $cohort->id)
        ->set('renameCohortName', 'Nouveau')
        ->call('renameCohort')
        ->assertHasNoErrors();

    expect($cohort->fresh()->name)->toBe('Nouveau');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SÉCURITÉ - étudiant / lambda interdit
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant ne peut pas créer de cohorte → 403', function (): void {
    $student = makeCohortStudent();

    Livewire::actingAs($student)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->assertForbidden();

    expect(Cohort::where('course_id', $this->courseA->id)->count())->toBe(0);
});

test('un user sans rôle ne peut pas affecter à une cohorte (mount → 403)', function (): void {
    $cohort = Cohort::create(['course_id' => $this->courseA->id, 'name' => 'Groupe']);
    $user   = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->assertForbidden();

    expect($cohort->fresh()->members()->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ANTI-IDOR - cohorte d'un autre cours / user non inscrit
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : affecter à une cohorte d\'un AUTRE cours est refusé, rien écrit', function (): void {
    $owner  = makeCohortRole($this->courseA, 'owner');
    $member = makeCohortEnrolled($this->courseA);

    // Cohorte appartenant au cours B (étranger au roster ouvert sur A).
    $foreignCohort = Cohort::create(['course_id' => $this->courseB->id, 'name' => 'Étrangère']);

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    expect(fn () => $component
        ->set('assignCohortId', $foreignCohort->id)
        ->set('assignEnrollmentUserId', $member->id)
        ->call('assignToCohort'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($foreignCohort->fresh()->members()->count())->toBe(0);
});

test('ANTI-IDOR : affecter un user NON inscrit au cours est refusé, rien écrit', function (): void {
    $owner  = makeCohortRole($this->courseA, 'owner');
    $cohort = Cohort::create(['course_id' => $this->courseA->id, 'name' => 'Groupe A']);

    // Un étudiant qui existe mais N'EST PAS inscrit au cours A.
    $outsider = makeCohortStudent();

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    expect(fn () => $component
        ->set('assignCohortId', $cohort->id)
        ->set('assignEnrollmentUserId', $outsider->id)
        ->call('assignToCohort'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($cohort->fresh()->members()->count())->toBe(0);
});

test('ANTI-IDOR : affecter un inscrit ANNULÉ (non actif) est refusé', function (): void {
    $owner   = makeCohortRole($this->courseA, 'owner');
    $cohort  = Cohort::create(['course_id' => $this->courseA->id, 'name' => 'Groupe A']);
    $student = makeCohortStudent();

    Enrollment::create([
        'course_id'    => $this->courseA->id,
        'user_id'      => $student->id,
        'status'       => 'cancelled',
        'source'       => 'admin',
        'enrolled_at'  => now(),
        'cancelled_at' => now(),
    ]);

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    expect(fn () => $component
        ->set('assignCohortId', $cohort->id)
        ->set('assignEnrollmentUserId', $student->id)
        ->call('assignToCohort'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($cohort->fresh()->members()->count())->toBe(0);
});

test('ANTI-IDOR : supprimer une cohorte d\'un autre cours est refusé, rien écrit', function (): void {
    $owner         = makeCohortRole($this->courseA, 'owner');
    $foreignCohort = Cohort::create(['course_id' => $this->courseB->id, 'name' => 'Étrangère']);

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('deleteCohort', $foreignCohort->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Cohort::find($foreignCohort->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. SUPPRESSION - retire les liens pivot, n'affecte PAS les inscriptions
// ─────────────────────────────────────────────────────────────────────────────

test('supprimer une cohorte retire les liens pivot sans toucher aux inscriptions', function (): void {
    $owner  = makeCohortRole($this->courseA, 'owner');
    $member = makeCohortEnrolled($this->courseA);
    $cohort = Cohort::create(['course_id' => $this->courseA->id, 'name' => 'Groupe A']);
    $cohort->members()->attach($member->id);

    expect($cohort->fresh()->members()->count())->toBe(1);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->call('deleteCohort', $cohort->id)
        ->assertHasNoErrors();

    // La cohorte et son lien pivot ont disparu...
    expect(Cohort::find($cohort->id))->toBeNull();
    expect(\Illuminate\Support\Facades\DB::table('academy_cohort_user')->where('cohort_id', $cohort->id)->count())->toBe(0);

    // ...mais l'inscription au cours est intacte et toujours active.
    $enrollment = Enrollment::where('course_id', $this->courseA->id)->where('user_id', $member->id)->first();
    expect($enrollment)->not->toBeNull();
    expect($enrollment->status)->toBe('active');
});
