<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - TUTEUR IA : FENÊTRE D'ACCÈS + QUOTA + RAPPEL (recommandation de
 * veille juillet 2026).
 *
 * Couvre :
 *  (a) drapeau academy.ai_tutor_access_control_enabled = false -> comportement
 *      du Tuteur IA inchangé (aucun blocage, même sans grant) ;
 *  (b) fenêtre relative_to_enrollment expirée -> accès refusé, message clair,
 *      le CHAT reste visible (historique conservé) ;
 *  (c) quota mensuel dépassé -> accès refusé même si la fenêtre est active ;
 *  (d) modifier la config du COURS après coup n'affecte PAS un grant déjà
 *      figé (preuve du non-rétroactif) ;
 *  (e) relance academy:tutor-access-remind idempotente (un seul envoi/jour/user).
 *
 * Garde-fou : SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Academy\Livewire\TutorChat;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\NotificationLog;
use Modules\Academy\Models\TutorAccessGrant;
use Modules\Academy\Services\TutorAccessService;
use Modules\AI\Services\AiService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('services.brevo.api_key', 'test-key');
    config()->set('mail.from.address', 'info@laveille.ai');
    config()->set('mail.from.name', 'La veille');

    Http::fake([
        'api.brevo.com/*' => Http::response(['messageId' => 'fake-123'], 201),
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe tac = tutor access control)
// ─────────────────────────────────────────────────────────────────────────────

function tacCourse(string $slug = 'tac-cours'): Course
{
    $course = new Course([
        'slug'        => $slug,
        'title'       => 'Cours TAC',
        'language'    => 'fr',
        'level'       => 'beginner',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
    ]);
    $course->save();

    return $course;
}

function tacLesson(Course $course, string $slug = 'tac-lecon'): Lesson
{
    $chapter = new Chapter(['course_id' => $course->id, 'title' => 'Chapitre TAC', 'position' => 1]);
    $chapter->save();

    $lesson = new Lesson([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon TAC',
        'slug'       => $slug,
        'position'   => 1,
        'summary'    => 'Résumé TAC',
    ]);
    $lesson->save();

    $item = new LessonItem([
        'lesson_id' => $lesson->id,
        'type'      => 'doc',
        'title'     => 'Item TAC',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Contenu de la leçon TAC'],
    ]);
    $item->save();

    return $lesson;
}

function tacEnroll(User $user, Course $course): Enrollment
{
    return Enrollment::create([
        'user_id'     => $user->id,
        'course_id'   => $course->id,
        'status'      => 'active',
        'source'      => 'manual',
        'enrolled_at' => now(),
    ]);
}

function tacMockAi(string $answer = 'Réponse test du tuteur IA'): void
{
    $mock = test()->mock(AiService::class);
    $mock->shouldReceive('chatWithHistory')->andReturn($answer);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF (défaut) — comportement inchangé, même sans grant
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau off : le tuteur repond normalement meme sans grant calcule', function (): void {
    config()->set('academy.ai_tutor_access_control_enabled', false);

    $course = tacCourse('tac-off');
    $lesson = tacLesson($course, 'tac-off-lecon');
    $user   = User::factory()->create();
    tacEnroll($user, $course);

    tacMockAi();

    $component = Livewire::actingAs($user)
        ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
        ->set('question', 'Une question quelconque ?')
        ->call('sendQuestion');

    $messages = $component->get('messages');
    expect($messages)->toHaveCount(2);
    expect($messages[1]['content'])->toBe('Réponse test du tuteur IA');
    expect($component->get('accessBlocked'))->toBeFalse();

    // Aucun grant n'a été calculé (NO-OP complet tant que le drapeau est off).
    expect(TutorAccessGrant::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Fenêtre relative_to_enrollment expirée -> accès refusé, message clair
// ─────────────────────────────────────────────────────────────────────────────

test('fenetre relative_to_enrollment expiree : acces refuse avec message clair, chat visible', function (): void {
    config()->set('academy.ai_tutor_access_control_enabled', true);

    $course = tacCourse('tac-expired');
    $course->update(['ai_tutor_window_type' => 'relative_to_enrollment', 'ai_tutor_window_days' => 7]);
    $lesson = tacLesson($course, 'tac-expired-lecon');

    $user = User::factory()->create();
    tacEnroll($user, $course); // observer calcule et FIGE le grant

    // On avance l'horloge du grant déjà calculé — simule une expiration réelle.
    TutorAccessGrant::where('user_id', $user->id)->where('course_id', $course->id)
        ->update(['access_expires_at' => now()->subDay()]);

    $component = Livewire::actingAs($user)
        ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
        ->set('question', 'Le cours est-il encore accessible ?')
        ->call('sendQuestion');

    $messages = $component->get('messages');

    // Le chat reste visible (historique conservé) : un seul message calme, jamais d'exception.
    expect($messages)->toHaveCount(1);
    expect($messages[0]['role'])->toBe('assistant');
    expect($messages[0]['content'])->toContain('terminé');
    expect($messages[0]['content'])->toContain('reste du cours');
    expect($messages[0]['error'])->toBeFalse();
    expect($component->get('accessBlocked'))->toBeTrue();

    // Le contenu du cours (leçon) n'est pas affecté : toujours accessible.
    expect(Lesson::find($lesson->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Quota mensuel dépassé -> accès refusé même si la fenêtre est active
// ─────────────────────────────────────────────────────────────────────────────

test('quota mensuel depasse : acces refuse meme si la fenetre est encore active', function (): void {
    config()->set('academy.ai_tutor_access_control_enabled', true);

    $course = tacCourse('tac-quota');
    $course->update(['ai_tutor_window_type' => 'none', 'ai_tutor_monthly_quota' => 1]);
    $lesson = tacLesson($course, 'tac-quota-lecon');

    $user = User::factory()->create();
    tacEnroll($user, $course);

    tacMockAi();

    // 1re question : sous le quota (1) -> répond normalement et incrémente à 1.
    Livewire::actingAs($user)
        ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
        ->set('question', 'Première question ?')
        ->call('sendQuestion')
        ->assertSet('accessBlocked', false);

    expect(TutorAccessGrant::where('user_id', $user->id)->where('course_id', $course->id)->first()->questions_used_current_period)->toBe(1);

    // 2e question : quota atteint (1/1) -> refusée, aucun appel IA.
    $component = Livewire::actingAs($user)
        ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
        ->set('question', 'Deuxième question ?')
        ->call('sendQuestion');

    $messages = $component->get('messages');
    $last     = end($messages);

    expect($last['content'])->toContain('quota');
    expect($component->get('accessBlocked'))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Config du cours modifiée après coup -> n'affecte PAS le grant déjà figé
// ─────────────────────────────────────────────────────────────────────────────

test('modifier la config du cours apres coup n affecte pas un grant deja fige', function (): void {
    config()->set('academy.ai_tutor_access_control_enabled', true);

    $course = tacCourse('tac-non-retro');
    $course->update(['ai_tutor_window_type' => 'relative_to_enrollment', 'ai_tutor_window_days' => 30]);

    $user = User::factory()->create();
    tacEnroll($user, $course); // grant figé : expire à J+30

    $grant = TutorAccessGrant::where('user_id', $user->id)->where('course_id', $course->id)->first();
    $originalExpiry = $grant->access_expires_at->toDateTimeString();

    // Le formateur RESSERRE la fenêtre à 1 jour (ex. après coup).
    $course->update(['ai_tutor_window_days' => 1]);

    $grant->refresh();

    // Le grant déjà calculé n'a PAS bougé : zéro effet rétroactif surprise.
    expect($grant->access_expires_at->toDateTimeString())->toBe($originalExpiry);

    // Une SECONDE inscription (nouvel utilisateur) suit, elle, la config à jour.
    $newUser = User::factory()->create();
    tacEnroll($newUser, $course);
    $newGrant = TutorAccessGrant::where('user_id', $newUser->id)->where('course_id', $course->id)->first();

    expect($newGrant->access_expires_at->diffInDays(now()))->toBeLessThanOrEqual(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Relance idempotente : jamais 2× le même cours/jour/user
// ─────────────────────────────────────────────────────────────────────────────

test('relance tutor-access-remind est idempotente (un seul envoi par cours/jour/user)', function (): void {
    config()->set('academy.ai_tutor_access_control_enabled', true);
    config()->set('academy.notifications.enabled', true);

    $course = tacCourse('tac-remind');
    $course->update([
        'ai_tutor_window_type'          => 'fixed_date',
        'ai_tutor_fixed_expiry_at'      => now()->addDay(),
        'ai_tutor_reminder_days_before' => 7,
    ]);

    $user = User::factory()->create();
    tacEnroll($user, $course); // grant figé : expire demain -> J-1 déclenche le rappel

    $this->artisan('academy:tutor-access-remind')->assertSuccessful();
    $this->artisan('academy:tutor-access-remind')->assertSuccessful();

    expect(NotificationLog::where('user_id', $user->id)->where('type', 'ai_tutor_access_reminder')->count())->toBe(1);
});

test('commande tutor-access-remind est un no-op si le drapeau est desactive', function (): void {
    config()->set('academy.ai_tutor_access_control_enabled', false);
    config()->set('academy.notifications.enabled', true);

    $this->artisan('academy:tutor-access-remind')->assertSuccessful();

    expect(NotificationLog::where('type', 'ai_tutor_access_reminder')->count())->toBe(0);
});
