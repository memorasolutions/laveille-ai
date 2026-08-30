<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - BUG-001 (P0) + BUG-002 (P1) : visibilité private/unlisted bloque
 * les étudiants inscrits sur les leçons et la page cours.
 *
 * Prouve que :
 *  - AVANT fix : étudiant inscrit + cours private/unlisted → 404 sur leçon et page cours (bug).
 *  - APRÈS fix : étudiant inscrit → 200, quel que soit private/unlisted/public.
 *  - Invariant : non-inscrit sur private → 404 (comportement attendu préservé).
 *  - Invariant : unlisted → page cours accessible par lien direct (200) pour tous.
 *  - Invariant : staff/admin → accès même sans preview, même sur private.
 *  - Non-régression : public + published → 200 pour tous (aucune casse).
 *
 * Préfixe helpers : vis_ (autonomes, sans conflit).
 * Garde-fou : skippé si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);

    // Seeders requis pour tester academy.manage (admin) et les rôles Spatie.
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers vis_ (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours minimal avec la visibilité et le statut donnés. */
function visCourse(string $visibility = 'public', string $status = 'published', string $slug = ''): Course
{
    static $counter = 0;
    $counter++;
    return Course::create([
        'slug'        => $slug ?: "vis-cours-{$visibility}-{$status}-{$counter}",
        'title'       => "Cours vis {$visibility} {$status}",
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => $visibility,
        'access_type' => 'free',
        'status'      => $status,
        'currency'    => 'CAD',
    ]);
}

/** Crée une leçon pour le cours donné (un chapitre + une leçon). */
function visLesson(Course $course): Lesson
{
    static $counter = 0;
    $counter++;
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => "Chapitre vis {$counter}",
        'position'  => 1,
    ]);
    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => "Leçon vis {$counter}",
        'slug'       => "lecon-vis-{$course->id}-{$counter}",
        'position'   => 1,
    ]);
}

/** Crée un étudiant avec une inscription active au cours donné. */
function visEnrolledStudent(Course $course): User
{
    $user = User::factory()->create();
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
    return $user;
}

/** Crée un utilisateur NON inscrit (étudiant lambda). */
function visAnonymousUser(): User
{
    return User::factory()->create();
}

/** Crée un administrateur ayant la permission academy.manage. */
function visAdmin(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('academy.manage');
    return $user;
}

/** Crée un propriétaire (owner) de cours via course_roles. */
function visCourseOwner(Course $course): User
{
    $user = User::factory()->create();
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $user->id,
        'role'      => 'owner',
    ]);
    return $user;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. BUG-001 — Leçon : accès par visibilité × rôle
// ─────────────────────────────────────────────────────────────────────────────

// BUG-001 (P0, corrigé) : un étudiant inscrit sur un cours private recevait un 404 sur la
// leçon au lieu d'y accéder. Vérifié le 2026-08-30 : le test de caractérisation (retiré,
// il assertait ce 404 et échouait par construction depuis le correctif) confirmait le bug ;
// le test ci-dessous verrouille le comportement correct.
test('BUG-001 — inscrit sur cours private accède à la leçon (200)', function (): void {
    $course  = visCourse('private');
    $lesson  = visLesson($course);
    $student = visEnrolledStudent($course);

    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});

test('BUG-001 — inscrit sur cours unlisted accède à la leçon (200)', function (): void {
    $course  = visCourse('unlisted');
    $lesson  = visLesson($course);
    $student = visEnrolledStudent($course);

    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});

test('BUG-001 — non-inscrit sur cours private ne peut pas accéder à la leçon (404)', function (): void {
    $course = visCourse('private');
    $lesson = visLesson($course);
    $user   = visAnonymousUser();

    $this->actingAs($user)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(404);
});

test('BUG-001 — non-inscrit sur cours unlisted ne peut pas accéder à la leçon (404)', function (): void {
    $course = visCourse('unlisted');
    $lesson = visLesson($course);
    $user   = visAnonymousUser();

    $this->actingAs($user)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(404);
});

test('BUG-001 — admin (academy.manage) accède à la leçon private sans preview (200)', function (): void {
    $course = visCourse('private');
    $lesson = visLesson($course);
    $admin  = visAdmin();

    $this->actingAs($admin)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});

test('BUG-001 — owner du cours accède à la leçon private sans preview (200)', function (): void {
    $course = visCourse('private');
    $lesson = visLesson($course);
    $owner  = visCourseOwner($course);

    $this->actingAs($owner)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});

// Non-régression : cours public toujours accessible à l'inscrit (aucune casse)
test('BUG-001 non-régression — inscrit sur cours public accède à la leçon (200)', function (): void {
    $course  = visCourse('public');
    $lesson  = visLesson($course);
    $student = visEnrolledStudent($course);

    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. BUG-002 — Page cours : accès par visibilité × rôle
// ─────────────────────────────────────────────────────────────────────────────

// BUG-002 (P1, corrigé) : un étudiant inscrit sur un cours private recevait un 404 sur la
// page du cours au lieu d'y accéder. Vérifié le 2026-08-30 : le test de caractérisation
// (retiré, il assertait ce 404 et échouait par construction depuis le correctif) confirmait
// le bug ; le test ci-dessous verrouille le comportement correct.
test('BUG-002 — inscrit sur cours private accède à la page cours (200)', function (): void {
    $course  = visCourse('private');
    $student = visEnrolledStudent($course);

    $this->actingAs($student)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 — inscrit sur cours unlisted accède à la page cours (200)', function (): void {
    $course  = visCourse('unlisted');
    $student = visEnrolledStudent($course);

    $this->actingAs($student)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 — unlisted : page cours accessible par lien direct (200) pour un anonyme', function (): void {
    $course = visCourse('unlisted');

    // Pas d'auth : le cours unlisted doit être accessible par lien direct.
    $this->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 — unlisted : page cours accessible par lien direct (200) pour un non-inscrit connecté', function (): void {
    $course = visCourse('unlisted');
    $user   = visAnonymousUser();

    $this->actingAs($user)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 — private : page cours inaccessible (404) pour un non-inscrit connecté', function (): void {
    $course = visCourse('private');
    $user   = visAnonymousUser();

    $this->actingAs($user)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(404);
});

test('BUG-002 — private : page cours inaccessible (404) pour un anonyme', function (): void {
    $course = visCourse('private');

    $this->get(route('academy.courses.show', $course))
        ->assertStatus(404);
});

test('BUG-002 — admin (academy.manage) accède à la page cours private sans preview (200)', function (): void {
    $course = visCourse('private');
    $admin  = visAdmin();

    $this->actingAs($admin)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 — owner du cours accède à la page cours private sans preview (200)', function (): void {
    $course = visCourse('private');
    $owner  = visCourseOwner($course);

    $this->actingAs($owner)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

// Non-régressions : cours public toujours accessible à tous (aucune casse)
test('BUG-002 non-régression — cours public accessible à un anonyme (200)', function (): void {
    $course = visCourse('public');

    $this->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 non-régression — cours public accessible à un non-inscrit connecté (200)', function (): void {
    $course = visCourse('public');
    $user   = visAnonymousUser();

    $this->actingAs($user)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});

test('BUG-002 non-régression — cours public accessible à un inscrit (200)', function (): void {
    $course  = visCourse('public');
    $student = visEnrolledStudent($course);

    $this->actingAs($student)
        ->get(route('academy.courses.show', $course))
        ->assertStatus(200);
});
