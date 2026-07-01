<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - NUDGES comportementaux de l'Académie. Prouve de façon autonome :
 *   - DOUBLE GARDE : drapeau nudges off => no-op ; interrupteur maître off => zéro envoi ;
 *   - CIBLAGE : un inscrit inactif reçoit nudge_inactivity, un inscrit actif ne reçoit rien ;
 *   - PLAFOND : au plus UN nudge par jour et par utilisateur (tous types confondus) ;
 *   - OPT-OUT : la préférence « nudge » désactivée bloque tout envoi (Loi 25) ;
 *   - PRIORITÉ : un jalon franchi (>= 50 %) déclenche un nudge de félicitations.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\NotificationLog;
use Modules\Academy\Models\Progress;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Academy\Services\NudgeService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    config()->set('services.brevo.api_key', 'test-key');
    config()->set('mail.from.address', 'info@laveille.ai');
    config()->set('mail.from.name', 'La veille');
    Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'fake-123'], 201)]);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe nudge)
// ─────────────────────────────────────────────────────────────────────────────

function nudgeCourse(string $slug = 'cours-nudge'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours Nudge',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function nudgeStudent(string $email): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Etudiant']);
    $u->assignRole('student');

    return $u;
}

function nudgeEnroll(Course $course, User $user, ?\Carbon\CarbonInterface $enrolledAt = null): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => $enrolledAt ?? now(),
    ]);
}

/** @param  int  $percent */
function nudgeProgress(Course $course, User $user, int $percent, \Carbon\CarbonInterface $lastActivityAt): void
{
    Progress::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'percent'            => $percent,
        'last_activity_at'   => $lastActivityAt,
        'required_total'     => 10,
        'required_completed' => (int) round($percent / 10),
    ]);
}

function nudgeNotif(): AcademyNotificationService
{
    return app(AcademyNotificationService::class);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. DOUBLE GARDE
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau nudges off : la commande est un no-op', function (): void {
    config()->set('academy.nudges_enabled', false);
    config()->set('academy.notifications.enabled', true);

    $course  = nudgeCourse();
    $inactif = nudgeStudent('dormant-nudge@example.test');
    nudgeEnroll($course, $inactif, now()->subDays(30));
    nudgeProgress($course, $inactif, 10, now()->subDays(20));

    $this->artisan('academy:nudge')
        ->expectsOutputToContain('Nudges désactivés')
        ->assertSuccessful();

    Http::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

test('interrupteur maître off : aucun nudge même si le drapeau est on', function (): void {
    config()->set('academy.nudges_enabled', true);
    config()->set('academy.notifications.enabled', false);

    $course  = nudgeCourse();
    $inactif = nudgeStudent('dormant-nudge@example.test');
    nudgeEnroll($course, $inactif, now()->subDays(30));
    nudgeProgress($course, $inactif, 10, now()->subDays(20));

    $this->artisan('academy:nudge')
        ->expectsOutputToContain('interrupteur maître off')
        ->assertSuccessful();

    Http::assertNothingSent();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CIBLAGE COMPORTEMENTAL
// ─────────────────────────────────────────────────────────────────────────────

test('un apprenant inactif reçoit un nudge d\'inactivité, un apprenant actif ne reçoit rien', function (): void {
    config()->set('academy.nudges_enabled', true);
    config()->set('academy.notifications.enabled', true);

    $course = nudgeCourse();

    // Adresses SANS chevauchement de sous-chaîne (sinon str_contains croise les deux).
    $inactif = nudgeStudent('dormant-nudge@example.test');
    nudgeEnroll($course, $inactif, now()->subDays(30));
    nudgeProgress($course, $inactif, 10, now()->subDays(20));

    $actif = nudgeStudent('present-nudge@example.test');
    nudgeEnroll($course, $actif, now());
    nudgeProgress($course, $actif, 10, now());

    $this->artisan('academy:nudge')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains(json_encode($request->data()), 'dormant-nudge@example.test'));
    Http::assertNotSent(fn ($request) => str_contains(json_encode($request->data()), 'present-nudge@example.test'));

    expect(NotificationLog::where('type', AcademyNotificationService::TYPE_NUDGE_INACTIVITY)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. PLAFOND ANTI-SPAM (1/jour/user)
// ─────────────────────────────────────────────────────────────────────────────

test('plafond : au plus un nudge par jour par utilisateur', function (): void {
    config()->set('academy.nudges_enabled', true);
    config()->set('academy.notifications.enabled', true);

    $c1 = nudgeCourse('c1');
    $c2 = nudgeCourse('c2');

    $inactif = nudgeStudent('dormant-nudge@example.test');

    nudgeEnroll($c1, $inactif, now()->subDays(30));
    nudgeProgress($c1, $inactif, 10, now()->subDays(20));

    nudgeEnroll($c2, $inactif, now()->subDays(30));
    nudgeProgress($c2, $inactif, 15, now()->subDays(22));

    // Deux passages le même jour ne produisent qu'UN seul nudge (plafond + dédoublonnage).
    $this->artisan('academy:nudge')->assertSuccessful();
    $this->artisan('academy:nudge')->assertSuccessful();

    Http::assertSentCount(1);
    expect(NotificationLog::where('user_id', $inactif->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. OPT-OUT (Loi 25)
// ─────────────────────────────────────────────────────────────────────────────

test('opt-out respecté : un apprenant qui a désactivé la préférence nudge ne reçoit rien', function (): void {
    config()->set('academy.nudges_enabled', true);
    config()->set('academy.notifications.enabled', true);

    $course  = nudgeCourse();
    $inactif = nudgeStudent('dormant-nudge@example.test');
    nudgeEnroll($course, $inactif, now()->subDays(30));
    nudgeProgress($course, $inactif, 10, now()->subDays(20));

    nudgeNotif()->setPreference($inactif, AcademyNotificationService::PREF_NUDGE, false);

    $this->artisan('academy:nudge')->assertSuccessful();

    Http::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. PRIORITÉ : jalon franchi (positif)
// ─────────────────────────────────────────────────────────────────────────────

test('jalon franchi (50%) : nudge de félicitations prioritaire', function (): void {
    config()->set('academy.nudges_enabled', true);
    config()->set('academy.notifications.enabled', true);

    $course = nudgeCourse();
    $user   = nudgeStudent('milestone-nudge@example.test');
    nudgeEnroll($course, $user, now());
    nudgeProgress($course, $user, 60, now());

    $this->artisan('academy:nudge')->assertSuccessful();

    expect(NotificationLog::where('type', AcademyNotificationService::TYPE_NUDGE_MILESTONE)->count())->toBe(1);
});
