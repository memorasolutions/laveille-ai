<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Calendrier GLOBAL (Vague 4, parité Moodle).
 *
 * Couvre :
 *  1. Drapeau OFF (défaut) : composant/route 404.
 *  2. Agrégation : plusieurs cours inscrits, manuels + dérivés (Assignment.due_at)
 *     + séances en direct (RÉUTILISATION de LiveSession, gâtée par son propre
 *     drapeau academy.live_sessions_enabled).
 *  3. ANTI-IDOR : un étudiant ne voit PAS les événements d'un cours où il n'est
 *     pas inscrit.
 *  4. Vue formateur/admin : un formateur voit les cours qu'il GÈRE (même sans y
 *     être inscrit) ; un admin (academy.manage) voit TOUS les cours.
 *  5. Bornage mensuel : un événement hors du mois affiché est exclu.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\GlobalCalendar;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\CalendarEvent;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LiveSession;
use Modules\Academy\Services\CalendarService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (noms distincts pour éviter les collisions avec les autres fichiers)
// ─────────────────────────────────────────────────────────────────────────────

function makeGCourse(string $suffix = ''): Course
{
    return Course::create([
        'slug'        => 'gcal-cours-' . $suffix . '-' . uniqid(),
        'title'       => 'Cours calendrier global ' . $suffix,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function makeGStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');
    return $u;
}

function makeGInstructor(): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    return $u;
}

function makeGAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole('admin');
    return $u;
}

function gEnroll(User $user, Course $course): void
{
    Enrollment::create([
        'user_id'   => $user->id,
        'course_id' => $course->id,
        'status'    => 'active',
        'source'    => 'free',
    ]);
}

function gGrantOwner(User $user, Course $course): void
{
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);
}

function gCalEvent(Course $course, User $creator, \Illuminate\Support\Carbon $startsAt): CalendarEvent
{
    return CalendarEvent::create([
        'course_id'  => $course->id,
        'title'      => 'Evenement global test',
        'type'       => 'manual',
        'starts_at'  => $startsAt,
        'created_by' => $creator->id,
    ]);
}

function gAssignmentDue(Course $course, \Illuminate\Support\Carbon $dueAt): Assignment
{
    return Assignment::create([
        'course_id'    => $course->id,
        'title'        => 'Devoir global test',
        'instructions' => null,
        'max_points'   => 100,
        'due_at'       => $dueAt,
        'is_published' => true,
        'position'     => 1,
    ]);
}

function gLiveSession(Course $course, \Illuminate\Support\Carbon $startsAt): LiveSession
{
    return LiveSession::create([
        'course_id' => $course->id,
        'title'     => 'Seance en direct globale test',
        'provider'  => 'meet',
        'join_url'  => 'https://meet.google.com/abc-defg-hij',
        'starts_at' => $startsAt,
    ]);
}

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 1. Drapeau OFF (défaut) : 404
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau OFF (defaut) : le calendrier global est 404', function (): void {
    config()->set('academy.global_calendar_enabled', false);
    $student = makeGStudent();

    Livewire::actingAs($student)
        ->test(GlobalCalendar::class)
        ->assertStatus(404);
});

test('drapeau OFF : la route academie/calendrier est 404', function (): void {
    config()->set('academy.global_calendar_enabled', false);
    $student = makeGStudent();

    $response = $this->actingAs($student)->get(route('academy.calendar.index'));

    $response->assertStatus(404);
});

test('drapeau ON : la page calendrier se rend correctement (grille + evenement visible)', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $student = makeGStudent();
    $course  = makeGCourse('render');
    gEnroll($student, $course);

    $owner = makeGInstructor();
    gGrantOwner($owner, $course);

    $mid = now()->startOfMonth()->addDays(3);
    gCalEvent($course, $owner, $mid);

    $response = $this->actingAs($student)->get(route('academy.calendar.index'));

    $response->assertOk();
    $response->assertSee('Mon calendrier', false);
    $response->assertSee('Evenement global test', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Agregation multi-cours : manuels + derives + seances en direct
// ─────────────────────────────────────────────────────────────────────────────

test('monthForUser agrege les evenements manuels de PLUSIEURS cours inscrits', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $student = makeGStudent();
    $courseA = makeGCourse('agg-a');
    $courseB = makeGCourse('agg-b');
    gEnroll($student, $courseA);
    gEnroll($student, $courseB);

    $ownerA = makeGInstructor();
    gGrantOwner($ownerA, $courseA);
    $ownerB = makeGInstructor();
    gGrantOwner($ownerB, $courseB);

    $mid = now()->startOfMonth()->addDays(10);
    gCalEvent($courseA, $ownerA, $mid);
    gCalEvent($courseB, $ownerB, $mid->copy()->addDay());

    $result = (new CalendarService())->monthForUser($student, $mid->year, $mid->month);

    expect($result)->toHaveCount(2);
    $courseIds = $result->pluck('course_id')->sort()->values()->all();
    expect($courseIds)->toEqualCanonicalizing([$courseA->id, $courseB->id]);
});

test('monthForUser inclut les echeances de devoirs (Assignment.due_at)', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $student = makeGStudent();
    $course  = makeGCourse('due');
    gEnroll($student, $course);

    $mid = now()->startOfMonth()->addDays(5);
    gAssignmentDue($course, $mid);

    $result = (new CalendarService())->monthForUser($student, $mid->year, $mid->month);

    expect($result)->toHaveCount(1);
    expect($result->first()['source'])->toBe('derived');
});

test('monthForUser inclut les seances en direct QUAND live_sessions_enabled est actif (reutilisation DRY)', function (): void {
    config()->set('academy.global_calendar_enabled', true);
    config()->set('academy.live_sessions_enabled', true);

    $student = makeGStudent();
    $course  = makeGCourse('live-on');
    gEnroll($student, $course);

    $mid = now()->startOfMonth()->addDays(8);
    gLiveSession($course, $mid);

    $result = (new CalendarService())->monthForUser($student, $mid->year, $mid->month);

    expect($result)->toHaveCount(1);
    expect($result->first()['source'])->toBe('live');
    expect($result->first()['type'])->toBe('live');
});

test('monthForUser EXCLUT les seances en direct QUAND live_sessions_enabled est desactive', function (): void {
    config()->set('academy.global_calendar_enabled', true);
    config()->set('academy.live_sessions_enabled', false);

    $student = makeGStudent();
    $course  = makeGCourse('live-off');
    gEnroll($student, $course);

    $mid = now()->startOfMonth()->addDays(8);
    gLiveSession($course, $mid);

    $result = (new CalendarService())->monthForUser($student, $mid->year, $mid->month);

    expect($result)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ANTI-IDOR : etudiant ne voit pas un cours ou il n'est pas inscrit
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : etudiant ne voit PAS les evenements d\'un cours ou il n\'est pas inscrit', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $student      = makeGStudent();
    $otherCourse  = makeGCourse('idor');
    $otherOwner   = makeGInstructor();
    gGrantOwner($otherOwner, $otherCourse);

    $mid = now()->startOfMonth()->addDays(5);
    gCalEvent($otherCourse, $otherOwner, $mid);

    // $student n'est inscrit nulle part et ne gere aucun cours.
    $result = (new CalendarService())->monthForUser($student, $mid->year, $mid->month);

    expect($result)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Vue formateur/admin : cours geres + tous les cours (academy.manage)
// ─────────────────────────────────────────────────────────────────────────────

test('formateur voit les evenements des cours qu\'il GERE meme sans y etre inscrit', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $instructor = makeGInstructor();
    $course     = makeGCourse('manage');
    gGrantOwner($instructor, $course);
    // Le formateur n'est PAS inscrit (enrollment) a son propre cours.

    $mid = now()->startOfMonth()->addDays(6);
    gCalEvent($course, $instructor, $mid);

    $result = (new CalendarService())->monthForUser($instructor, $mid->year, $mid->month);

    expect($result)->toHaveCount(1);
});

test('formateur A ne voit PAS les evenements du cours du formateur B (pas gere, pas inscrit)', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $instructorA = makeGInstructor();
    $courseB     = makeGCourse('cross');
    $instructorB = makeGInstructor();
    gGrantOwner($instructorB, $courseB);

    $mid = now()->startOfMonth()->addDays(6);
    gCalEvent($courseB, $instructorB, $mid);

    $result = (new CalendarService())->monthForUser($instructorA, $mid->year, $mid->month);

    expect($result)->toBeEmpty();
});

test('admin (academy.manage) voit les evenements de TOUS les cours', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $admin  = makeGAdmin();
    $course = makeGCourse('admin-view');
    $owner  = makeGInstructor();
    gGrantOwner($owner, $course);
    // $admin n'est ni inscrit ni gerant explicite de ce cours.

    $mid = now()->startOfMonth()->addDays(4);
    gCalEvent($course, $owner, $mid);

    $result = (new CalendarService())->monthForUser($admin, $mid->year, $mid->month);

    expect($result)->toHaveCount(1);
});

test('utilisateur double casquette (inscrit ET gerant d\'un AUTRE cours) voit l\'union des deux', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $user = makeGInstructor();

    $courseEnrolled = makeGCourse('union-enrolled');
    gEnroll($user, $courseEnrolled);

    $courseManaged = makeGCourse('union-managed');
    gGrantOwner($user, $courseManaged);

    $mid = now()->startOfMonth()->addDays(9);
    gCalEvent($courseEnrolled, $user, $mid);
    gCalEvent($courseManaged, $user, $mid->copy()->addDay());

    $result = (new CalendarService())->monthForUser($user, $mid->year, $mid->month);

    expect($result)->toHaveCount(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Bornage mensuel
// ─────────────────────────────────────────────────────────────────────────────

test('monthForUser exclut un evenement HORS du mois demande', function (): void {
    config()->set('academy.global_calendar_enabled', true);

    $student = makeGStudent();
    $course  = makeGCourse('bornage');
    gEnroll($student, $course);

    $thisMonth = now()->startOfMonth();
    $nextMonth = $thisMonth->copy()->addMonthNoOverflow()->addDays(2);

    gCalEvent($course, $student, $nextMonth);

    $result = (new CalendarService())->monthForUser($student, $thisMonth->year, $thisMonth->month);

    expect($result)->toBeEmpty();
});

test('retrocompat : utilisateur sans inscription ni cours gere recoit une collection vide (pas d\'exception)', function (): void {
    config()->set('academy.global_calendar_enabled', true);
    $student = makeGStudent();

    $now = now();
    $result = (new CalendarService())->monthForUser($student, $now->year, $now->month);

    expect($result)->toBeEmpty();
});
