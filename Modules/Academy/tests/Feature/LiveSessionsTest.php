<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - SÉANCES EN DIRECT / VISIOCONFÉRENCE NATIVES.
 *
 * Couvre :
 *  (a) drapeau academy.live_sessions_enabled = false -> composants 404, commande no-op ;
 *  (b) un formateur (owner) crée une séance ; un non-staff ne peut pas (403) ;
 *  (c) un inscrit qui joint = présence enregistrée UNE SEULE fois (idempotent) ;
 *  (d) un non-inscrit ne voit pas / ne peut pas joindre ;
 *  (e) relance academy:live-remind idempotente (jamais 2× la même séance/jour/user).
 *
 * Garde-fou : SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseLiveSessions;
use Modules\Academy\Livewire\LiveSessionsManager;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LiveSession;
use Modules\Academy\Models\LiveSessionAttendance;
use Modules\Academy\Models\NotificationLog;
use Modules\Academy\Services\AcademyNotificationService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.live_sessions_enabled', true);
    config()->set('services.brevo.api_key', 'test-key');
    config()->set('mail.from.address', 'info@laveille.ai');
    config()->set('mail.from.name', 'La veille');

    Http::fake([
        'api.brevo.com/*' => Http::response(['messageId' => 'fake-123'], 201),
    ]);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe live)
// ─────────────────────────────────────────────────────────────────────────────

function liveUser(string $email, string $role = 'student'): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Personne']);
    $u->assignRole($role);

    return $u;
}

function liveCourse(): Course
{
    return Course::create([
        'slug'        => 'cours-live-' . uniqid(),
        'title'       => 'Cours Live',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function liveOwner(Course $course, string $email): User
{
    $owner = liveUser($email, 'instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

function liveEnrol(Course $course, User $user): void
{
    Enrollment::create([
        'user_id'     => $user->id,
        'course_id'   => $course->id,
        'status'      => 'active',
        'source'      => 'free',
        'enrolled_at' => now(),
    ]);
}

function liveSession(Course $course, ?User $creator = null, ?\Illuminate\Support\Carbon $startsAt = null): LiveSession
{
    return LiveSession::create([
        'course_id'  => $course->id,
        'title'      => 'Atelier IA en direct',
        'provider'   => 'meet',
        'join_url'   => 'https://meet.google.com/abc-defg-hij',
        'starts_at'  => $startsAt ?? now()->addDays(2),
        'created_by' => $creator?->id,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF : composants 404, commande no-op
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau off : le gestionnaire formateur renvoie 404', function (): void {
    config()->set('academy.live_sessions_enabled', false);

    $course = liveCourse();
    $owner  = liveOwner($course, 'owner-off@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->assertStatus(404);
});

test('drapeau off : la vue apprenant renvoie 404', function (): void {
    config()->set('academy.live_sessions_enabled', false);

    $course = liveCourse();
    $student = liveUser('stu-off@ex.test');
    liveEnrol($course, $student);

    $this->actingAs($student);

    Livewire::test(CourseLiveSessions::class, ['course' => $course])
        ->assertStatus(404);
});

test('drapeau off : la commande live-remind est un no-op (aucun log)', function (): void {
    config()->set('academy.live_sessions_enabled', false);
    config()->set('academy.notifications.enabled', true);

    liveSession(liveCourse(), null, now()->addHours(3));

    $this->artisan('academy:live-remind')->assertSuccessful();

    expect(NotificationLog::where('type', 'live_reminder')->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Formateur crée une séance ; un non-staff ne peut pas (403)
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur (owner) crée une séance en direct', function (): void {
    $course = liveCourse();
    $owner  = liveOwner($course, 'owner-create@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->set('title', 'Séance de lancement')
        ->set('provider', 'meet')
        ->set('join_url', 'https://meet.google.com/xyz-abcd-efg')
        ->set('starts_at', now('America/Toronto')->addDays(3)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(1);
    $session = LiveSession::first();
    expect($session->title)->toBe('Séance de lancement');
    expect($session->provider)->toBe('meet');
    expect($session->created_by)->toBe($owner->id);
});

test('une URL meet.google.com est acceptee (aucun rejet de domaine)', function (): void {
    $course = liveCourse();
    $owner  = liveOwner($course, 'owner-url@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->set('title', 'Meet valide')
        ->set('join_url', 'https://meet.google.com/abc-defg-hij')
        ->set('starts_at', now('America/Toronto')->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors(['join_url']);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(1);
});

test('un non-staff ne peut pas ouvrir le gestionnaire formateur (403)', function (): void {
    $course  = liveCourse();
    $student = liveUser('stu-403@ex.test');
    liveEnrol($course, $student);

    $this->actingAs($student);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->assertStatus(403);
});

test('le formateur supprime une seance via la confirmation inline (deux temps)', function (): void {
    $course  = liveCourse();
    $owner   = liveOwner($course, 'owner-delete@ex.test');
    $session = liveSession($course, $owner);

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->call('confirmDelete', $session->id)
        ->assertSet('confirmingDeleteId', $session->id)
        ->call('deleteSession', $session->id)
        ->assertSet('confirmingDeleteId', null)
        ->assertHasNoErrors();

    expect(LiveSession::whereKey($session->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Inscrit qui joint : présence enregistrée UNE seule fois (idempotent)
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit qui joint enregistre sa presence une seule fois (idempotent)', function (): void {
    $course  = liveCourse();
    $student = liveUser('stu-join@ex.test');
    liveEnrol($course, $student);
    $session = liveSession($course);

    $this->actingAs($student);

    $component = Livewire::test(CourseLiveSessions::class, ['course' => $course]);

    // Deux clics « Rejoindre » : une seule ligne de présence.
    $component->call('join', $session->id)->assertHasNoErrors();
    $component->call('join', $session->id)->assertHasNoErrors();

    $rows = LiveSessionAttendance::where('live_session_id', $session->id)
        ->where('user_id', $student->id)
        ->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->joined_at)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Non-inscrit : ne voit pas / ne peut pas joindre
// ─────────────────────────────────────────────────────────────────────────────

test('un non-inscrit ne peut pas ouvrir la vue apprenant (403)', function (): void {
    $course   = liveCourse();
    $outsider = liveUser('outsider@ex.test');
    liveSession($course);

    $this->actingAs($outsider);

    Livewire::test(CourseLiveSessions::class, ['course' => $course])
        ->assertStatus(403);
});

test('un non-inscrit qui force join n enregistre aucune presence', function (): void {
    $course   = liveCourse();
    // Le staff a accès mais n'est PAS inscrit : il ne doit pas polluer la présence.
    $owner   = liveOwner($course, 'owner-nopresence@ex.test');
    $session = liveSession($course);

    $this->actingAs($owner);

    Livewire::test(CourseLiveSessions::class, ['course' => $course])
        ->call('join', $session->id)
        ->assertHasNoErrors();

    expect(LiveSessionAttendance::where('live_session_id', $session->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Relance idempotente : jamais 2× la même séance/jour/user
// ─────────────────────────────────────────────────────────────────────────────

test('relance live-remind est idempotente (un seul envoi par seance/jour/user)', function (): void {
    config()->set('academy.notifications.enabled', true);

    $course  = liveCourse();
    $student = liveUser('remind-live@ex.test');
    liveEnrol($course, $student);
    // Séance dans 3 h : dans la fenêtre de rappel (défaut 24 h).
    liveSession($course, null, now()->addHours(3));

    $this->artisan('academy:live-remind')->assertSuccessful();
    $this->artisan('academy:live-remind')->assertSuccessful();

    expect(NotificationLog::where('user_id', $student->id)->where('type', 'live_reminder')->count())->toBe(1);
});
