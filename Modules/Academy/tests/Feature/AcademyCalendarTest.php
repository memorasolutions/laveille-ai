<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Calendrier d'echeances V5-b.
 *
 * Couvre :
 *  1. upcomingForUser : scope strict par inscription active (anti-IDOR).
 *  2. forCourse : fusion manuels + derives (Assignment.due_at).
 *  3. CRUD via CourseCalendar Livewire : gate manageStructure.
 *  4. Anti-IDOR cross-course : un gerant ne peut pas supprimer l'evenement
 *     d'un AUTRE cours via le meme composant.
 *  5. Export iCal : acces gate + contenu VEVENT.
 *  6. Dashboard "echeances a venir" : section masquee si vide.
 *  7. Retrocompat : cours sans evenement (liste vide, pas d'exception).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseCalendar;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\CalendarEvent;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Services\CalendarService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function makeCalCourse(string $suffix = ''): Course
{
    return Course::create([
        'slug'        => 'cal-cours-' . $suffix . '-' . uniqid(),
        'title'       => 'Cours calendrier ' . $suffix,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function enrollActive(User $user, Course $course): Enrollment
{
    return Enrollment::create([
        'user_id'   => $user->id,
        'course_id' => $course->id,
        'status'    => 'active',
        'source'    => 'free',
    ]);
}

function makeInstructor(): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    return $u;
}

function makeStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');
    return $u;
}

function grantOwner(User $user, Course $course): void
{
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $user->id,
        'role'      => 'owner',
    ]);
}

function makeAssignmentWithDue(Course $course, \Carbon\Carbon $dueAt, bool $published = true): Assignment
{
    return Assignment::create([
        'course_id'    => $course->id,
        'title'        => 'Devoir test',
        'instructions' => null,
        'max_points'   => 100,
        'due_at'       => $dueAt,
        'is_published' => $published,
        'position'     => 1,
    ]);
}

function makeCalEvent(Course $course, User $creator, \Carbon\Carbon $startsAt): CalendarEvent
{
    return CalendarEvent::create([
        'course_id'  => $course->id,
        'title'      => 'Evenement test',
        'type'       => 'manual',
        'starts_at'  => $startsAt,
        'created_by' => $creator->id,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Setup commun
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 1. CalendarService::upcomingForUser - scope strict par inscription
// ─────────────────────────────────────────────────────────────────────────────

test('upcomingForUser retourne vide si aucune inscription active', function (): void {
    $user    = makeStudent();
    $service = new CalendarService();

    $result = $service->upcomingForUser($user);

    expect($result)->toBeEmpty();
});

test('upcomingForUser inclut les echeances futures des cours inscrits', function (): void {
    $user   = makeStudent();
    $course = makeCalCourse('incl');
    enrollActive($user, $course);

    $instructor = makeInstructor();
    grantOwner($instructor, $course);
    makeCalEvent($course, $instructor, now()->addDays(3));

    $service = new CalendarService();
    $result  = $service->upcomingForUser($user);

    expect($result)->toHaveCount(1);
    expect($result->first()['title'])->toBe('Evenement test');
});

test('upcomingForUser exclut les echeances passees', function (): void {
    $user   = makeStudent();
    $course = makeCalCourse('excl-past');
    enrollActive($user, $course);

    $instructor = makeInstructor();
    grantOwner($instructor, $course);
    // Evenement dans le passe
    makeCalEvent($course, $instructor, now()->subDays(2));

    $service = new CalendarService();
    $result  = $service->upcomingForUser($user);

    expect($result)->toBeEmpty();
});

test('upcomingForUser - ANTI-IDOR : etudiant ne voit pas les cours ou il n\'est pas inscrit', function (): void {
    $user    = makeStudent();
    $other   = makeCalCourse('other-idor');

    $instructor = makeInstructor();
    grantOwner($instructor, $other);
    makeCalEvent($other, $instructor, now()->addDays(5));

    // Pas d'inscription de $user sur $other
    $service = new CalendarService();
    $result  = $service->upcomingForUser($user);

    expect($result)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CalendarService::forCourse - fusion manuels + derives
// ─────────────────────────────────────────────────────────────────────────────

test('forCourse fusionne evenements manuels ET derives (Assignment.due_at)', function (): void {
    $course     = makeCalCourse('fusion');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);

    // 1 evenement manuel
    makeCalEvent($course, $instructor, now()->addDays(7));

    // 1 devoir publie avec echeance
    makeAssignmentWithDue($course, now()->addDays(5));

    $service = new CalendarService();
    $result  = $service->forCourse($course);

    expect($result)->toHaveCount(2);

    $sources = $result->pluck('source')->sort()->values()->all();
    expect($sources)->toContain('manual');
    expect($sources)->toContain('derived');
});

test('forCourse exclut les devoirs non publies de la liste derivee', function (): void {
    $course     = makeCalCourse('excl-draft');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);

    // Devoir non publie : ne doit pas apparaitre dans le calendrier
    makeAssignmentWithDue($course, now()->addDays(3), published: false);

    $service = new CalendarService();
    $result  = $service->forCourse($course);

    expect($result)->toBeEmpty();
});

test('forCourse retourne vide pour un cours sans evenements (retrocompat)', function (): void {
    $course  = makeCalCourse('empty');
    $service = new CalendarService();
    $result  = $service->forCourse($course);

    expect($result)->toBeEmpty();
});

test('forCourse trie les evenements par date ascendante', function (): void {
    $course     = makeCalCourse('sort');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);

    $late  = makeCalEvent($course, $instructor, now()->addDays(10));
    $early = makeCalEvent($course, $instructor, now()->addDays(2));

    $service = new CalendarService();
    $result  = $service->forCourse($course);

    expect($result->first()['event_id'])->toBe($early->id);
    expect($result->last()['event_id'])->toBe($late->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. CRUD via CourseCalendar Livewire - gate manageStructure
// ─────────────────────────────────────────────────────────────────────────────

test('formateur owner peut creer un evenement manuel via CourseCalendar', function (): void {
    $course     = makeCalCourse('crud');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);

    Livewire::actingAs($instructor)
        ->test(CourseCalendar::class, ['course' => $course])
        ->call('openCreate')
        ->set('evTitle', 'Session live')
        ->set('evType', 'live')
        ->set('evStartsAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(CalendarEvent::where('course_id', $course->id)->count())->toBe(1);
    expect(CalendarEvent::where('course_id', $course->id)->first()->title)->toBe('Session live');
});

test('etudiant inscrit ne peut pas creer un evenement (gate manageStructure)', function (): void {
    $course  = makeCalCourse('student-gate');
    $student = makeStudent();
    enrollActive($student, $course);

    Livewire::actingAs($student)
        ->test(CourseCalendar::class, ['course' => $course])
        ->call('openCreate')
        ->assertForbidden();
});

test('formateur peut supprimer un evenement de SON cours', function (): void {
    $course     = makeCalCourse('del');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);
    $ev = makeCalEvent($course, $instructor, now()->addDays(3));

    Livewire::actingAs($instructor)
        ->test(CourseCalendar::class, ['course' => $course])
        ->call('confirmRemove', $ev->id)
        ->call('remove', $ev->id)
        ->assertHasNoErrors();

    expect(CalendarEvent::where('id', $ev->id)->exists())->toBeFalse();
    // Soft-delete : toujours en base avec deleted_at
    expect(CalendarEvent::withTrashed()->where('id', $ev->id)->exists())->toBeTrue();
});

test('validation : titre vide est rejete', function (): void {
    $course     = makeCalCourse('val');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);

    Livewire::actingAs($instructor)
        ->test(CourseCalendar::class, ['course' => $course])
        ->call('openCreate')
        ->set('evTitle', '')
        ->set('evStartsAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->set('evType', 'manual')
        ->call('save')
        ->assertHasErrors(['evTitle']);
});

test('validation : type hors liste blanche est rejete', function (): void {
    $course     = makeCalCourse('type-wl');
    $instructor = makeInstructor();
    grantOwner($instructor, $course);

    Livewire::actingAs($instructor)
        ->test(CourseCalendar::class, ['course' => $course])
        ->call('openCreate')
        ->set('evTitle', 'Test')
        ->set('evType', 'invalide')
        ->set('evStartsAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['evType']);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Anti-IDOR cross-course
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : gerant du cours A ne peut pas supprimer l\'evenement du cours B', function (): void {
    $courseA    = makeCalCourse('idor-a');
    $courseB    = makeCalCourse('idor-b');
    $instructorA = makeInstructor();
    grantOwner($instructorA, $courseA);

    $instructorB = makeInstructor();
    grantOwner($instructorB, $courseB);

    $evB = makeCalEvent($courseB, $instructorB, now()->addDays(4));

    // instructorA tente de supprimer $evB via le composant montee sur courseA
    Livewire::actingAs($instructorA)
        ->test(CourseCalendar::class, ['course' => $courseA])
        ->call('confirmRemove', $evB->id)
        ->call('remove', $evB->id);

    // L'evenement du cours B ne doit pas avoir ete supprime
    expect(CalendarEvent::where('id', $evB->id)->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Export iCal
// ─────────────────────────────────────────────────────────────────────────────

test('etudiant inscrit peut telecharger le fichier .ics', function (): void {
    $course  = makeCalCourse('ical');
    $student = makeStudent();
    enrollActive($student, $course);

    $instructor = makeInstructor();
    grantOwner($instructor, $course);
    makeCalEvent($course, $instructor, now()->addDays(2));

    $response = $this->actingAs($student)
        ->get(route('academy.courses.calendar.ical', $course->slug));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/calendar; charset=utf-8');
    $response->assertSee('BEGIN:VCALENDAR', false);
    $response->assertSee('VEVENT', false);
    $response->assertSee('Evenement test', false);
});

test('visiteur non connecte est refuse pour le .ics', function (): void {
    $course = makeCalCourse('ical-anon');

    $response = $this->get(route('academy.courses.calendar.ical', $course->slug));

    $response->assertRedirect(route('login'));
});

test('etudiant non inscrit est refuse pour le .ics (anti-IDOR)', function (): void {
    $course  = makeCalCourse('ical-idor');
    $student = makeStudent();
    // Pas d'inscription

    $response = $this->actingAs($student)
        ->get(route('academy.courses.calendar.ical', $course->slug));

    $response->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Dashboard : bandeau "echeances a venir"
// ─────────────────────────────────────────────────────────────────────────────

test('dashboard etudiant voit le bandeau echeances si une echeance future existe', function (): void {
    $course  = makeCalCourse('dash-show');
    $student = makeStudent();
    enrollActive($student, $course);

    $instructor = makeInstructor();
    grantOwner($instructor, $course);
    makeCalEvent($course, $instructor, now()->addDays(3));

    $response = $this->actingAs($student)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertSee('Echeances a venir', false);
});

test('dashboard masque le bandeau echeances si aucune echeance future', function (): void {
    $course  = makeCalCourse('dash-hide');
    $student = makeStudent();
    enrollActive($student, $course);
    // Aucun evenement dans ce cours

    $response = $this->actingAs($student)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertDontSee('id="academy-echeances"', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Page calendrier du cours - acces et affichage
// ─────────────────────────────────────────────────────────────────────────────

test('etudiant inscrit peut acceder a la page calendrier du cours', function (): void {
    $course  = makeCalCourse('page');
    $student = makeStudent();
    enrollActive($student, $course);

    $response = $this->actingAs($student)
        ->get(route('academy.courses.calendar', $course->slug));

    $response->assertOk();
    $response->assertSee('Calendrier des echeances', false);
});

test('etudiant non inscrit est refuse sur la page calendrier (anti-IDOR)', function (): void {
    $course  = makeCalCourse('page-idor');
    $student = makeStudent();
    // Pas d'inscription

    $response = $this->actingAs($student)
        ->get(route('academy.courses.calendar', $course->slug));

    $response->assertForbidden();
});
