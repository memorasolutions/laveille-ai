<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - RÉPÉTITION ESPACÉE (SRS) NATIVE.
 *
 * Couvre :
 *  (a) drapeau academy.srs_enabled = false -> aucune carte créée, commande no-op ;
 *  (b) SM-2 : bonne réponse allonge l'intervalle, mauvaise le réinitialise ;
 *  (c) dueFor() ne renvoie QUE les cartes dues de CET utilisateur (anti-IDOR) ;
 *  (d) relance academy:srs-remind idempotente (jamais 2× le même jour/user).
 *
 * Garde-fou : SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\NotificationLog;
use Modules\Academy\Models\SrsCard;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Academy\Services\SrsService;

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

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe srs)
// ─────────────────────────────────────────────────────────────────────────────

function srsUser(string $email): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Apprenant']);
    $u->assignRole('student');

    return $u;
}

function srsLessonWithItems(): Lesson
{
    $course  = Course::create([
        'slug'        => 'cours-srs-' . uniqid(),
        'title'       => 'Cours SRS',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'C', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'l-' . $chapter->id, 'position' => 1]);

    // Un concept (doc) + un quiz = 2 cartes révisables ; un item vidéo = ignoré.
    LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'doc', 'title' => 'Concept clé', 'position' => 1, 'payload' => ['body' => 'Texte du concept.']]);
    LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q1', 'position' => 2, 'payload' => ['prompt' => 'Question ?', 'explanation' => 'Réponse.']]);
    LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'Vidéo', 'position' => 3, 'payload' => []]);

    return $lesson->fresh(['lessonItems', 'chapter.course']);
}

/**
 * Crée un cours + chapitre + leçon minimaux et renvoie [course_id, lesson_id]
 * réels (les cartes SRS ont des FK vers courses et lessons).
 *
 * @return array{0:int,1:int}
 */
function srsCourseLesson(): array
{
    $course  = Course::create([
        'slug'        => 'cours-srs-fk-' . uniqid(),
        'title'       => 'Cours SRS FK',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'C', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'l-fk-' . $chapter->id, 'position' => 1]);

    return [$course->id, $lesson->id];
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF : aucune carte, commande no-op
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau off : enqueueForLesson ne cree aucune carte', function (): void {
    config()->set('academy.srs_enabled', false);

    $user   = srsUser('off@ex.test');
    $lesson = srsLessonWithItems();

    $created = app(SrsService::class)->enqueueForLesson($user, $lesson);

    expect($created)->toBe(0);
    expect(SrsCard::count())->toBe(0);
});

test('drapeau off : la commande srs-remind est un no-op (aucun log)', function (): void {
    config()->set('academy.srs_enabled', false);
    config()->set('academy.notifications.enabled', true);

    $this->artisan('academy:srs-remind')->assertSuccessful();

    expect(NotificationLog::where('type', 'srs_reminder')->count())->toBe(0);
});

test('drapeau on : enqueueForLesson cree une carte par item revisable (doc+quiz), idempotent', function (): void {
    config()->set('academy.srs_enabled', true);

    $user   = srsUser('on@ex.test');
    $lesson = srsLessonWithItems();

    $first  = app(SrsService::class)->enqueueForLesson($user, $lesson);
    $second = app(SrsService::class)->enqueueForLesson($user, $lesson);

    expect($first)->toBe(2);   // doc + quiz (la vidéo est ignorée)
    expect($second)->toBe(0);  // idempotent : rien de neuf au 2e appel
    expect(SrsCard::where('user_id', $user->id)->count())->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) SM-2 : succès allonge l'intervalle, échec le réinitialise
// ─────────────────────────────────────────────────────────────────────────────

test('SM-2 : bonnes reponses successives allongent l intervalle', function (): void {
    config()->set('academy.srs_enabled', true);

    $user = srsUser('sm2@ex.test');
    [$courseId, $lessonId] = srsCourseLesson();
    $card = SrsCard::create([
        'user_id'     => $user->id,
        'course_id'   => $courseId,
        'lesson_id'   => $lessonId,
        'source_type' => LessonItem::class,
        'source_id'   => 1,
        'front'       => 'Q',
        'back'        => 'A',
        'due_at'      => now(),
    ]);

    $srs = app(SrsService::class);

    $srs->review($card, 5);            // 1re bonne réponse
    expect($card->repetitions)->toBe(1);
    expect($card->interval_days)->toBe(1);

    $srs->review($card, 5);            // 2e bonne réponse
    expect($card->repetitions)->toBe(2);
    expect($card->interval_days)->toBe(6);

    $srs->review($card, 5);            // 3e : interval = ceil(6 * EF) > 6
    expect($card->repetitions)->toBe(3);
    expect($card->interval_days)->toBeGreaterThan(6);
});

test('SM-2 : mauvaise reponse reinitialise repetitions et intervalle a 1 jour', function (): void {
    config()->set('academy.srs_enabled', true);

    $user = srsUser('fail@ex.test');
    [$courseId, $lessonId] = srsCourseLesson();
    $card = SrsCard::create([
        'user_id'     => $user->id,
        'course_id'   => $courseId,
        'lesson_id'   => $lessonId,
        'source_type' => LessonItem::class,
        'source_id'   => 2,
        'front'       => 'Q',
        'back'        => 'A',
        'due_at'      => now(),
    ]);

    $srs = app(SrsService::class);
    $srs->review($card, 5);   // succès : monte les répétitions
    $srs->review($card, 5);
    expect($card->repetitions)->toBe(2);

    $srs->review($card, 1);   // échec (q < 3)
    expect($card->repetitions)->toBe(0);
    expect($card->interval_days)->toBe(1);
    expect($card->ease_factor)->toBeGreaterThanOrEqual(1.3); // jamais sous le plancher
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) dueFor() : seulement les cartes dues de CET utilisateur
// ─────────────────────────────────────────────────────────────────────────────

test('dueFor ne renvoie que les cartes dues de CET utilisateur', function (): void {
    config()->set('academy.srs_enabled', true);

    $me    = srsUser('me@ex.test');
    $other = srsUser('other@ex.test');
    [$courseId, $lessonId] = srsCourseLesson();

    // Ma carte due (hier).
    SrsCard::create(['user_id' => $me->id, 'course_id' => $courseId, 'lesson_id' => $lessonId, 'source_type' => LessonItem::class, 'source_id' => 10, 'front' => 'A', 'due_at' => now()->subDay()]);
    // Ma carte NON due (demain).
    SrsCard::create(['user_id' => $me->id, 'course_id' => $courseId, 'lesson_id' => $lessonId, 'source_type' => LessonItem::class, 'source_id' => 11, 'front' => 'B', 'due_at' => now()->addDay()]);
    // Carte due d'un AUTRE utilisateur (ne doit jamais remonter).
    SrsCard::create(['user_id' => $other->id, 'course_id' => $courseId, 'lesson_id' => $lessonId, 'source_type' => LessonItem::class, 'source_id' => 12, 'front' => 'C', 'due_at' => now()->subDay()]);

    $due = app(SrsService::class)->dueFor($me);

    expect($due)->toHaveCount(1);
    expect($due->first()->source_id)->toBe(10);
    expect($due->pluck('user_id')->unique()->all())->toBe([$me->id]);
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Relance idempotente : jamais 2× le même jour/user
// ─────────────────────────────────────────────────────────────────────────────

test('relance srs-remind est idempotente (un seul envoi par jour et par utilisateur)', function (): void {
    config()->set('academy.srs_enabled', true);
    config()->set('academy.notifications.enabled', true);

    $user = srsUser('remind@ex.test');
    [$courseId, $lessonId] = srsCourseLesson();
    SrsCard::create(['user_id' => $user->id, 'course_id' => $courseId, 'lesson_id' => $lessonId, 'source_type' => LessonItem::class, 'source_id' => 20, 'front' => 'A', 'due_at' => now()->subDay()]);

    // 1er passage : envoie et journalise.
    $this->artisan('academy:srs-remind')->assertSuccessful();
    // 2e passage le même jour : aucun envoi supplémentaire (dédoublonnage).
    $this->artisan('academy:srs-remind')->assertSuccessful();

    expect(NotificationLog::where('user_id', $user->id)->where('type', 'srs_reminder')->count())->toBe(1);
});
