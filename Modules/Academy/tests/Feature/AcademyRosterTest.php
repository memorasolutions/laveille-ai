<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Roster (inscriptions) + rôles de cours (CourseRoster, PHASE 4 / FE-4).
 *
 * Prouve que CHAQUE mutation est gardée par une autorisation SERVEUR (OWASP A01) :
 *  - admin / owner du cours A → inscrit/désinscrit un étudiant ET ajoute/retire un
 *    formateur sur A ;
 *  - INSTRUCTOR (non-owner) de A → gère le roster (manageEnrollments) mais NE PEUT
 *    PAS gérer les rôles (manageRoles = owner/admin) → 403 sur addRoleByEmail ;
 *  - ANTI-ESCALADE : formateur du cours A ne peut rien gérer du cours B → 403 ;
 *  - ANTI-IDOR : agir sur un Enrollment / CourseRole d'un AUTRE cours → refusé,
 *    rien écrit ;
 *  - le rôle 'owner' n'est jamais retirable ;
 *  - étudiant / user sans rôle → interdit.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseRoster;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = makeRosterCourse('roster-a', 'Cours A');
    $this->courseB = makeRosterCourse('roster-b', 'Cours B');
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function makeRosterCourse(string $slug, string $title): Course
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

/** Helper : admin academy.manage. */
function makeRosterAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

/** Helper : formateur avec un rôle de cours donné (owner par défaut). */
function makeRosterRole(Course $course, string $role = 'owner'): User
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

/** Helper : un étudiant (compte existant, sans rôle de cours). */
function makeRosterStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. ROSTER - owner/admin inscrit et désinscrit un étudiant sur SON cours
// ─────────────────────────────────────────────────────────────────────────────

test('owner peut inscrire un étudiant existant par courriel', function (): void {
    $owner   = makeRosterRole($this->courseA, 'owner');
    $student = makeRosterStudent();

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('enrollEmail', $student->email)
        ->call('enrollByEmail')
        ->assertHasNoErrors();

    $enrollment = Enrollment::where('course_id', $this->courseA->id)
        ->where('user_id', $student->id)
        ->first();

    expect($enrollment)->not->toBeNull();
    expect($enrollment->status)->toBe('active');
    expect($enrollment->source)->toBe('admin');
});

test('inscrire un courriel sans compte ne crée AUCUN compte et affiche une erreur', function (): void {
    $owner = makeRosterRole($this->courseA, 'owner');

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('enrollEmail', 'inconnu@exemple.ca')
        ->call('enrollByEmail')
        ->assertHasErrors('enrollEmail');

    expect(User::where('email', 'inconnu@exemple.ca')->exists())->toBeFalse();
    expect(Enrollment::where('course_id', $this->courseA->id)->count())->toBe(0);
});

test('owner peut désinscrire (status cancelled) un inscrit de SON cours', function (): void {
    $admin   = makeRosterAdmin();
    $owner   = makeRosterRole($this->courseA, 'owner');
    $student = makeRosterStudent();

    $enrollment = Enrollment::create([
        'course_id'   => $this->courseA->id,
        'user_id'     => $student->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->call('cancelEnrollment', $enrollment->id)
        ->assertHasNoErrors();

    expect($enrollment->fresh()->status)->toBe('cancelled');
    expect($enrollment->fresh()->cancelled_at)->not->toBeNull();
});

test('admin peut inscrire un étudiant sur n\'importe quel cours', function (): void {
    $student = makeRosterStudent();

    Livewire::actingAs(makeRosterAdmin())
        ->test(CourseRoster::class, ['course' => $this->courseB])
        ->set('enrollEmail', $student->email)
        ->call('enrollByEmail')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseB->id)->where('user_id', $student->id)->where('status', 'active')->exists())
        ->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. RÔLES DE COURS - owner/admin ajoute et retire un formateur sur SON cours
// ─────────────────────────────────────────────────────────────────────────────

test('owner peut ajouter un formateur par courriel', function (): void {
    $owner    = makeRosterRole($this->courseA, 'owner');
    $teammate = makeRosterStudent();

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('roleEmail', $teammate->email)
        ->set('roleName', 'instructor')
        ->call('addRoleByEmail')
        ->assertHasNoErrors();

    expect(CourseRole::where('course_id', $this->courseA->id)->where('user_id', $teammate->id)->where('role', 'instructor')->exists())
        ->toBeTrue();
});

test('owner peut retirer un rôle d\'équipe (non owner)', function (): void {
    $owner    = makeRosterRole($this->courseA, 'owner');
    $teammate = makeRosterStudent();

    $role = CourseRole::create([
        'course_id' => $this->courseA->id,
        'user_id'   => $teammate->id,
        'role'      => 'assistant',
    ]);

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->call('removeRole', $role->id)
        ->assertHasNoErrors();

    expect(CourseRole::find($role->id))->toBeNull();
});

test('un rôle forgé hors liste blanche est rejeté (pas owner attribuable)', function (): void {
    $owner    = makeRosterRole($this->courseA, 'owner');
    $teammate = makeRosterStudent();

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('roleEmail', $teammate->email)
        ->set('roleName', 'owner') // hors liste blanche {instructor,assistant,editor}
        ->call('addRoleByEmail')
        ->assertHasErrors('roleName');

    expect(CourseRole::where('course_id', $this->courseA->id)->where('user_id', $teammate->id)->exists())
        ->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. INSTRUCTOR (non-owner) - gère le roster MAIS PAS les rôles (manageRoles)
// ─────────────────────────────────────────────────────────────────────────────

test('un instructor non-owner peut gérer le roster de SON cours', function (): void {
    $instructor = makeRosterRole($this->courseA, 'instructor');
    $student    = makeRosterStudent();

    Livewire::actingAs($instructor)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('enrollEmail', $student->email)
        ->call('enrollByEmail')
        ->assertHasNoErrors();

    expect(Enrollment::where('course_id', $this->courseA->id)->where('user_id', $student->id)->where('status', 'active')->exists())
        ->toBeTrue();
});

test('un instructor non-owner NE PEUT PAS ajouter un rôle (manageRoles = owner/admin) → 403', function (): void {
    $instructor = makeRosterRole($this->courseA, 'instructor');
    $teammate   = makeRosterStudent();

    Livewire::actingAs($instructor)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->set('roleEmail', $teammate->email)
        ->set('roleName', 'assistant')
        ->call('addRoleByEmail')
        ->assertForbidden();

    expect(CourseRole::where('course_id', $this->courseA->id)->where('user_id', $teammate->id)->exists())
        ->toBeFalse();
});

test('un instructor non-owner NE PEUT PAS retirer un rôle → 403', function (): void {
    $instructor = makeRosterRole($this->courseA, 'instructor');
    $teammate   = makeRosterStudent();

    $role = CourseRole::create([
        'course_id' => $this->courseA->id,
        'user_id'   => $teammate->id,
        'role'      => 'assistant',
    ]);

    Livewire::actingAs($instructor)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->call('removeRole', $role->id)
        ->assertForbidden();

    expect(CourseRole::find($role->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ANTI-ESCALADE - formateur de A ne gère rien de B
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-ESCALADE : owner de A ne peut PAS ouvrir le roster du cours B', function (): void {
    $owner = makeRosterRole($this->courseA, 'owner');

    Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseB])
        ->assertForbidden();
});

test('ANTI-ESCALADE : owner de A ne peut pas inscrire sur B même en forgeant le courseId', function (): void {
    $owner   = makeRosterRole($this->courseA, 'owner');
    $student = makeRosterStudent();

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    // Forge le courseId côté navigateur vers B (non autorisé) puis tente d'inscrire.
    // enrollByEmail() re-résout B et appelle authorize('manageEnrollments', B) → 403.
    $component->set('courseId', $this->courseB->id)
        ->set('enrollEmail', $student->email)
        ->call('enrollByEmail')
        ->assertForbidden();

    expect(Enrollment::where('course_id', $this->courseB->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ANTI-IDOR - agir sur un Enrollment / CourseRole d'un AUTRE cours est refusé
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : désinscrire un Enrollment d\'un autre cours est refusé, rien écrit', function (): void {
    $owner   = makeRosterRole($this->courseA, 'owner');
    $student = makeRosterStudent();

    // Un Enrollment qui appartient au cours B (étranger au roster ouvert sur A).
    $foreign = Enrollment::create([
        'course_id'   => $this->courseB->id,
        'user_id'     => $student->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    // resolveEnrollmentFor() ne trouve pas l'inscription dans le cours A → ModelNotFound.
    expect(fn () => $component->call('cancelEnrollment', $foreign->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($foreign->fresh()->status)->toBe('active');
});

test('ANTI-IDOR : retirer un CourseRole d\'un autre cours est refusé, rien écrit', function (): void {
    // L'owner de A est aussi owner de B (manageRoles OK sur A et B), mais le roster
    // est ouvert sur A : le rôle de B ne doit pas être atteignable depuis A.
    $owner = makeRosterRole($this->courseA, 'owner');
    CourseRole::create(['course_id' => $this->courseB->id, 'user_id' => $owner->id, 'role' => 'owner']);

    $teammate = makeRosterStudent();
    $foreignRole = CourseRole::create([
        'course_id' => $this->courseB->id,
        'user_id'   => $teammate->id,
        'role'      => 'assistant',
    ]);

    $component = Livewire::actingAs($owner)
        ->test(CourseRoster::class, ['course' => $this->courseA]);

    expect(fn () => $component->call('removeRole', $foreignRole->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(CourseRole::find($foreignRole->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. OWNER NON RETIRABLE
// ─────────────────────────────────────────────────────────────────────────────

test('retirer le rôle owner est refusé, le rôle subsiste', function (): void {
    $admin = makeRosterAdmin();
    $owner = makeRosterRole($this->courseA, 'owner');

    $ownerRole = CourseRole::where('course_id', $this->courseA->id)
        ->where('user_id', $owner->id)
        ->where('role', 'owner')
        ->firstOrFail();

    Livewire::actingAs($admin)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->call('removeRole', $ownerRole->id)
        ->assertHasErrors('roleEmail');

    expect(CourseRole::find($ownerRole->id))->not->toBeNull();
    expect($ownerRole->fresh()->role)->toBe('owner');
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. ÉTUDIANT / SANS RÔLE - interdit (entrée)
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant ne peut pas ouvrir le roster', function (): void {
    $student = makeRosterStudent();

    Livewire::actingAs($student)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->assertForbidden();
});

test('un utilisateur sans aucun rôle ne peut pas ouvrir le roster', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CourseRoster::class, ['course' => $this->courseA])
        ->assertForbidden();
});
