<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - V5-c NOTIFICATIONS COURRIEL D'ACTIVITÉ. Prouve, de façon AUTONOME
 * (helpers préfixés v5c) :
 *
 *  - INTERRUPTEUR MAITRE : enabled=false (défaut) => AUCUN envoi (Brevo jamais appelé,
 *    journal vide) sur chaque type ;
 *  - PRÉFÉRENCE respectée : un étudiant qui a désactivé un type ne le reçoit pas ;
 *  - SCOPE strict : une annonce ne part QU'aux inscrits ACTIFS (jamais un non-inscrit
 *    ni une inscription annulée) ;
 *  - FORUM : la réponse notifie l'auteur du sujet, jamais l'auteur de la réponse ;
 *  - RAPPELS : idempotents (un 2e appel ne renvoie pas la même échéance) ;
 *  - RÉTROCOMPAT : préférence par défaut = défaut de config quand aucune ligne.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Academy\Models\Announcement;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\ForumPost;
use Modules\Academy\Models\ForumTopic;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\NotificationLog;
use Modules\Academy\Models\NotificationPreference;
use Modules\Academy\Services\AcademyNotificationService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    // Brevo « configuré » pour les tests + interception HTTP (aucun appel réseau réel).
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
// Helpers autonomes (préfixe v5c)
// ─────────────────────────────────────────────────────────────────────────────

function v5cCourse(string $slug = 'cours-v5c'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V5-c',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v5cStudent(string $email): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Etudiant']);
    $u->assignRole('student');

    return $u;
}

function v5cEnroll(Course $course, User $user, string $status = 'active'): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => $status,
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v5cService(): AcademyNotificationService
{
    return app(AcademyNotificationService::class);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. INTERRUPTEUR MAITRE (défaut OFF) = aucun envoi
// ─────────────────────────────────────────────────────────────────────────────

test('interrupteur maitre par defaut = false', function (): void {
    // Pas de config()->set('academy.notifications.enabled') : on lit le défaut.
    expect(v5cService()->isMasterEnabled())->toBeFalse();
});

test('interrupteur off : une annonce publiee n\'envoie AUCUN courriel', function (): void {
    config()->set('academy.notifications.enabled', false);

    $course  = v5cCourse();
    $student = v5cStudent('off-student@example.test');
    v5cEnroll($course, $student);

    $announcement = Announcement::create([
        'course_id'    => $course->id,
        'author_id'    => $student->id,
        'title'        => 'Annonce',
        'body'         => 'Corps',
        'published_at' => now(),
    ]);

    $count = v5cService()->announcementPublished($announcement);

    expect($count)->toBe(0);
    Http::assertNothingSent();
});

test('interrupteur off : rappel, correction et completion n\'envoient rien', function (): void {
    config()->set('academy.notifications.enabled', false);

    $course  = v5cCourse();
    $student = v5cStudent('off2@example.test');

    expect(v5cService()->graded($student, $course, 'Devoir 1', 80))->toBeFalse();
    expect(v5cService()->dueReminder($student, [
        'id' => 'asgn-1', 'title' => 'X', 'starts_at' => now()->addDay(), 'course_title' => 'C',
    ]))->toBeFalse();

    Http::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. INTERRUPTEUR ON : envoi + scope + préférence
// ─────────────────────────────────────────────────────────────────────────────

test('annonce publiee : envoi UNIQUEMENT aux inscrits actifs (scope strict)', function (): void {
    config()->set('academy.notifications.enabled', true);

    $course   = v5cCourse();
    $actif    = v5cStudent('actif@example.test');
    $annule   = v5cStudent('annule@example.test');
    $jamais   = v5cStudent('jamais@example.test'); // non inscrit
    v5cEnroll($course, $actif, 'active');
    v5cEnroll($course, $annule, 'cancelled');

    $announcement = Announcement::create([
        'course_id'    => $course->id,
        'author_id'    => $actif->id,
        'title'        => 'Nouvelle annonce',
        'body'         => 'Bonjour',
        'published_at' => now(),
    ]);

    $count = v5cService()->announcementPublished($announcement);

    expect($count)->toBe(1);
    Http::assertSent(fn ($req) => str_contains(json_encode($req->data()), 'actif@example.test'));
    Http::assertNotSent(fn ($req) => str_contains(json_encode($req->data()), 'annule@example.test'));
    Http::assertNotSent(fn ($req) => str_contains(json_encode($req->data()), 'jamais@example.test'));
});

test('preference desactivee : l\'etudiant ne recoit pas ce type', function (): void {
    config()->set('academy.notifications.enabled', true);

    $course   = v5cCourse();
    $optOut   = v5cStudent('optout@example.test');
    v5cEnroll($course, $optOut, 'active');

    // L'étudiant désactive les annonces.
    v5cService()->setPreference($optOut, AcademyNotificationService::TYPE_ANNOUNCEMENT, false);

    $announcement = Announcement::create([
        'course_id'    => $course->id,
        'author_id'    => $optOut->id,
        'title'        => 'Annonce',
        'body'         => 'Corps',
        'published_at' => now(),
    ]);

    $count = v5cService()->announcementPublished($announcement);

    expect($count)->toBe(0);
    Http::assertNothingSent();
});

test('preference par defaut = defaut de config quand aucune ligne (retrocompat)', function (): void {
    $student = v5cStudent('default@example.test');

    expect(NotificationPreference::count())->toBe(0);
    expect(v5cService()->isEnabledFor($student, AcademyNotificationService::TYPE_GRADED))->toBeTrue();
    expect(v5cService()->preferencesFor($student))
        ->toHaveKey(AcademyNotificationService::TYPE_COURSE_COMPLETED, true);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. FORUM : auteur du sujet notifié, jamais l'auteur de la réponse
// ─────────────────────────────────────────────────────────────────────────────

test('forum : la reponse notifie l\'auteur du sujet, pas l\'auteur de la reponse', function (): void {
    config()->set('academy.notifications.enabled', true);

    $course  = v5cCourse();
    $auteur  = v5cStudent('auteur@example.test');
    $repond  = v5cStudent('repondeur@example.test');
    v5cEnroll($course, $auteur, 'active');
    v5cEnroll($course, $repond, 'active');

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'C', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'l-'.$chapter->id, 'position' => 1]);
    $item    = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'forum', 'title' => 'F', 'position' => 1, 'payload' => []]);

    $topic = ForumTopic::create(['lesson_item_id' => $item->id, 'user_id' => $auteur->id, 'title' => 'Sujet', 'body' => 'B']);
    $post  = ForumPost::create(['topic_id' => $topic->id, 'user_id' => $repond->id, 'body' => 'Une reponse']);

    $sent = v5cService()->forumReply($topic, $post, $course);

    expect($sent)->toBeTrue();
    Http::assertSent(fn ($req) => str_contains(json_encode($req->data()), 'auteur@example.test'));
    Http::assertNotSent(fn ($req) => str_contains(json_encode($req->data()), 'repondeur@example.test'));
});

test('forum : aucune notification si l\'auteur repond a son propre sujet', function (): void {
    config()->set('academy.notifications.enabled', true);

    $course = v5cCourse();
    $auteur = v5cStudent('solo@example.test');
    v5cEnroll($course, $auteur, 'active');

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'C', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'l-'.$chapter->id, 'position' => 1]);
    $item    = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'forum', 'title' => 'F', 'position' => 1, 'payload' => []]);
    $topic   = ForumTopic::create(['lesson_item_id' => $item->id, 'user_id' => $auteur->id, 'title' => 'S', 'body' => 'B']);
    $post    = ForumPost::create(['topic_id' => $topic->id, 'user_id' => $auteur->id, 'body' => 'auto-reponse']);

    expect(v5cService()->forumReply($topic, $post, $course))->toBeFalse();
    Http::assertNothingSent();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. RAPPELS D'ÉCHÉANCE : idempotence
// ─────────────────────────────────────────────────────────────────────────────

test('rappel d\'echeance idempotent : deux appels = un seul envoi', function (): void {
    config()->set('academy.notifications.enabled', true);

    $student = v5cStudent('reminder@example.test');
    $event   = [
        'id'           => 'asgn-42',
        'title'        => 'Devoir final',
        'starts_at'    => now()->addDay(),
        'course_title' => 'Cours V5-c',
        'course_slug'  => 'cours-v5c',
    ];

    expect(v5cService()->dueReminder($student, $event))->toBeTrue();
    expect(v5cService()->dueReminder($student, $event))->toBeFalse(); // dédoublonné

    expect(NotificationLog::where('user_id', $student->id)->count())->toBe(1);
    Http::assertSentCount(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. COURS COMPLÉTÉ : envoi simple
// ─────────────────────────────────────────────────────────────────────────────

test('cours complete : courriel envoye a l\'etudiant', function (): void {
    config()->set('academy.notifications.enabled', true);

    $course  = v5cCourse();
    $student = v5cStudent('graduate@example.test');

    $cert = CertificateIssued::create([
        'user_id'           => $student->id,
        'course_id'         => $course->id,
        'serial'            => 'ACAD-TEST123456',
        'verification_hash' => str_repeat('a', 64),
        'public_url_slug'   => 'cert-test-1',
        'issued_at'         => now(),
        'hours_earned'      => 2,
        'final_score'       => 100,
    ]);

    expect(v5cService()->courseCompleted($student, $course, $cert))->toBeTrue();
    Http::assertSent(fn ($req) => str_contains(json_encode($req->data()), 'graduate@example.test'));
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. COMMANDE : sortie precoce si interrupteur off
// ─────────────────────────────────────────────────────────────────────────────

test('commande academy:send-due-reminders sort sans envoi si interrupteur off', function (): void {
    config()->set('academy.notifications.enabled', false);

    $course  = v5cCourse();
    $student = v5cStudent('cmd@example.test');
    v5cEnroll($course, $student, 'active');

    $this->artisan('academy:send-due-reminders')
        ->expectsOutputToContain('aucun rappel envoyé')
        ->assertSuccessful();

    Http::assertNothingSent();
});
