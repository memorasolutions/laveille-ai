<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — xAPI léger (couche d'abstraction actor-verb-object, dette F16).
 *
 * Prouve que :
 *  - le drapeau academy.xapi_enabled OFF (défaut) ne crée AUCUN statement, quel
 *    que soit l'enchaînement d'actions pédagogiques réalisé (scénario complet) ;
 *  - drapeau ON : chaque point d'émission branché écrit bien un statement avec
 *    le verbe/objet attendu et les bonnes données :
 *      - complétion de leçon      → verb=completed, object_type=lesson
 *      - complétion de cours      → verb=completed, object_type=course
 *      - tentative de quiz soumise→ verb=attempted, object_type=quiz
 *      - XP crédité               → verb=earned,    object_type=xp_event
 *      - révision SRS             → verb=reviewed,  object_type=srs_card
 *      - présence séance en direct→ verb=attended,  object_type=live_session
 *  - idempotence : un statement 'completed'/'course' n'est jamais dupliqué par
 *    des recalculs de progression répétés ; une révision SRS répétée écrit
 *    bien PLUSIEURS statements (chaque révision est une action distincte).
 *  - aucune régression sur les tests déjà existants des zones touchées
 *    (SrsTest, AcademyGamificationTest — lancés séparément, pas ici).
 *
 * Autonome : helpers préfixés `xapi`. SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\LiveSession;
use Modules\Academy\Models\SrsCard;
use Modules\Academy\Models\XapiStatement;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\GamificationService;
use Modules\Academy\Services\SrsService;
use Modules\Academy\Services\XapiRecorderService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    // Défaut EXPLICITE : chaque test active le(s) drapeau(x) qu'il veut vérifier.
    config()->set('academy.xapi_enabled', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers xapi (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function xapiStudent(?string $email = null): User
{
    $u = User::factory()->create($email !== null ? ['email' => $email] : []);
    $u->assignRole('student');

    return $u;
}

function xapiCourse(?string $slug = null): Course
{
    return Course::create([
        'slug'        => $slug ?? ('xapi-cours-'.uniqid()),
        'title'       => 'Cours xAPI',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un item de leçon requis (document) dans un cours donné. */
function xapiLessonItem(Course $course, string $suffix = '1'): LessonItem
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => "xapi-lecon-{$suffix}-{$course->id}",
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'document',
        'title'       => 'Item',
        'position'    => 1,
        'is_required' => true,
    ]);
}

function xapiEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF (défaut) = AUCUN statement, quel que soit le scénario joué
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau xapi OFF (défaut) ne crée aucun statement pour un scénario complet', function (): void {
    config()->set('academy.xapi_enabled', false);
    config()->set('academy.gamification_enabled', true);
    config()->set('academy.srs_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $item   = xapiLessonItem($course);
    xapiEnroll($course, $user);

    // Complétion de leçon (déclenche en cascade : XP, SRS enqueue...).
    CompletionService::markComplete($user, $item);

    // Révision SRS directe (si une carte a été mise en file).
    $card = SrsCard::where('user_id', $user->id)->first();
    if ($card !== null) {
        app(SrsService::class)->review($card, 5);
    }

    // Crédit XP générique direct.
    app(GamificationService::class)->award($user, $course, 'manual', 1, 'xapi_off_test', 10);

    expect(XapiStatement::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Drapeau ON — complétion de leçon
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau on : compléter une leçon crée un statement completed/lesson', function (): void {
    config()->set('academy.xapi_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $item   = xapiLessonItem($course);
    xapiEnroll($course, $user);

    CompletionService::markComplete($user, $item, score: 8);

    $statement = XapiStatement::forUser($user->id)
        ->forVerb(XapiRecorderService::VERB_COMPLETED)
        ->forObject(XapiRecorderService::OBJECT_LESSON, $item->id)
        ->first();

    expect($statement)->not->toBeNull();
    expect($statement->result['score'] ?? null)->toBe(8);
    expect($statement->context['course_id'] ?? null)->toBe($course->id);
    expect($statement->raw_payload)->toBeArray();
});

test('drapeau on : rejouer markComplete pour la même leçon n\'écrit pas de 2e statement', function (): void {
    config()->set('academy.xapi_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $item   = xapiLessonItem($course);
    xapiEnroll($course, $user);

    CompletionService::markComplete($user, $item);
    CompletionService::markComplete($user, $item); // idempotent côté Completion

    expect(
        XapiStatement::forUser($user->id)
            ->forVerb(XapiRecorderService::VERB_COMPLETED)
            ->forObject(XapiRecorderService::OBJECT_LESSON, $item->id)
            ->count()
    )->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Drapeau ON — complétion de cours (idempotence sur recalculs multiples)
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau on : compléter le seul item requis du cours crée un statement completed/course', function (): void {
    config()->set('academy.xapi_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $item   = xapiLessonItem($course);
    xapiEnroll($course, $user);

    // Un seul item requis dans le cours → le compléter complète le cours à 100 %.
    CompletionService::markComplete($user, $item);

    $statement = XapiStatement::forUser($user->id)
        ->forVerb(XapiRecorderService::VERB_COMPLETED)
        ->forObject(XapiRecorderService::OBJECT_COURSE, $course->id)
        ->first();

    expect($statement)->not->toBeNull();

    // Un recalcul supplémentaire (ex. rappelé ailleurs) ne duplique jamais le
    // statement de complétion de cours (onceOnly=true par défaut).
    \Modules\Academy\Services\ProgressService::recalculate($user, $course);

    expect(
        XapiStatement::forUser($user->id)
            ->forVerb(XapiRecorderService::VERB_COMPLETED)
            ->forObject(XapiRecorderService::OBJECT_COURSE, $course->id)
            ->count()
    )->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Drapeau ON — tentative de quiz soumise (via HTTP, route réelle)
// ─────────────────────────────────────────────────────────────────────────────

function xapiQuizScaffold(Course $course): array
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'xapi-quiz-'.$chapter->id, 'position' => 1]);
    $item    = LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => ['passing_score' => 50],
        'is_required' => false,
    ]);

    return ['course' => $course, 'lesson' => $lesson, 'item' => $item];
}

test('drapeau on : soumettre un quiz crée un statement attempted/quiz', function (): void {
    config()->set('academy.xapi_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $scaffold = xapiQuizScaffold($course);
    xapiEnroll($course, $user);

    $round = [
        ['type' => 'qcm', 'question' => 'Capitale ?', 'choices' => ['Bonne', 'Mauvaise'], 'correct' => 0, 'points' => 1],
    ];

    $this->actingAs($user)
        ->withSession([
            "academy.quiz.{$scaffold['item']->id}" => [
                'questions'  => $round,
                'started_at' => now()->toIso8601String(),
            ],
        ])
        ->post(route('academy.quiz.submit', [$scaffold['course']->slug, $scaffold['lesson']->id, $scaffold['item']->id]), [
            'answers' => [0],
        ]);

    $attempt = \Modules\Academy\Models\QuizAttempt::where('user_id', $user->id)
        ->where('lesson_item_id', $scaffold['item']->id)
        ->first();

    expect($attempt)->not->toBeNull();

    $statement = XapiStatement::forUser($user->id)
        ->forVerb(XapiRecorderService::VERB_ATTEMPTED)
        ->forObject(XapiRecorderService::OBJECT_QUIZ, $attempt->id)
        ->first();

    expect($statement)->not->toBeNull();
    expect($statement->result['passed'] ?? null)->toBe(true);
    expect($statement->context['lesson_item_id'] ?? null)->toBe($scaffold['item']->id);
});

test('drapeau on : deux tentatives de quiz successives créent DEUX statements distincts', function (): void {
    config()->set('academy.xapi_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $scaffold = xapiQuizScaffold($course);
    xapiEnroll($course, $user);

    $round = [
        ['type' => 'qcm', 'question' => 'Capitale ?', 'choices' => ['Bonne', 'Mauvaise'], 'correct' => 0, 'points' => 1],
    ];

    for ($i = 0; $i < 2; $i++) {
        $this->actingAs($user)
            ->withSession([
                "academy.quiz.{$scaffold['item']->id}" => [
                    'questions'  => $round,
                    'started_at' => now()->toIso8601String(),
                ],
            ])
            ->post(route('academy.quiz.submit', [$scaffold['course']->slug, $scaffold['lesson']->id, $scaffold['item']->id]), [
                'answers' => [1], // réponse fausse : pas de crédit XP en jeu, testons juste la trace
            ]);
    }

    expect(
        XapiStatement::forUser($user->id)
            ->forVerb(XapiRecorderService::VERB_ATTEMPTED)
            ->where('object_type', XapiRecorderService::OBJECT_QUIZ)
            ->count()
    )->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Drapeau ON — XP crédité
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau on : un crédit XP générique crée un statement earned/xp_event', function (): void {
    config()->set('academy.xapi_enabled', true);
    config()->set('academy.gamification_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();

    $event = app(GamificationService::class)->award($user, $course, 'manual', 1, 'xapi_earn_test', 42);

    expect($event)->not->toBeNull();

    $statement = XapiStatement::forUser($user->id)
        ->forVerb(XapiRecorderService::VERB_EARNED)
        ->forObject(XapiRecorderService::OBJECT_XP_EVENT, $event->id)
        ->first();

    expect($statement)->not->toBeNull();
    expect($statement->result['points'] ?? null)->toBe(42);
    expect($statement->result['reason'] ?? null)->toBe('xapi_earn_test');
});

test('drapeau on mais gamification OFF : aucun XP crédité donc aucun statement earned', function (): void {
    config()->set('academy.xapi_enabled', true);
    config()->set('academy.gamification_enabled', false);

    $user   = xapiStudent();
    $course = xapiCourse();

    $event = app(GamificationService::class)->award($user, $course, 'manual', 1, 'xapi_earn_off', 42);

    expect($event)->toBeNull();
    expect(XapiStatement::forVerb(XapiRecorderService::VERB_EARNED)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (f) Drapeau ON — révision SRS (répétable : plusieurs statements attendus)
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau on : réviser une carte SRS crée un statement reviewed/srs_card', function (): void {
    config()->set('academy.xapi_enabled', true);
    config()->set('academy.srs_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $item   = xapiLessonItem($course, 'srs-1');
    $card   = SrsCard::create([
        'user_id'     => $user->id,
        'course_id'   => $course->id,
        'lesson_id'   => $item->lesson_id,
        'source_type' => LessonItem::class,
        'source_id'   => 1,
        'front'       => 'Q',
        'back'        => 'A',
        'due_at'      => now(),
    ]);

    app(SrsService::class)->review($card, 5);

    $statement = XapiStatement::forUser($user->id)
        ->forVerb(XapiRecorderService::VERB_REVIEWED)
        ->forObject(XapiRecorderService::OBJECT_SRS_CARD, $card->id)
        ->first();

    expect($statement)->not->toBeNull();
    expect($statement->result['quality'] ?? null)->toBe(5);
});

test('drapeau on : réviser la MÊME carte plusieurs fois crée PLUSIEURS statements (action répétable)', function (): void {
    config()->set('academy.xapi_enabled', true);
    config()->set('academy.srs_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    $item   = xapiLessonItem($course, 'srs-2');
    $card   = SrsCard::create([
        'user_id'     => $user->id,
        'course_id'   => $course->id,
        'lesson_id'   => $item->lesson_id,
        'source_type' => LessonItem::class,
        'source_id'   => 2,
        'front'       => 'Q',
        'back'        => 'A',
        'due_at'      => now(),
    ]);

    $srs = app(SrsService::class);
    $srs->review($card, 5);
    $srs->review($card, 4);
    $srs->review($card, 5);

    expect(
        XapiStatement::forUser($user->id)
            ->forVerb(XapiRecorderService::VERB_REVIEWED)
            ->forObject(XapiRecorderService::OBJECT_SRS_CARD, $card->id)
            ->count()
    )->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// (g) Drapeau ON — présence à une séance en direct
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau on : rejoindre une séance en direct crée un statement attended/live_session', function (): void {
    config()->set('academy.xapi_enabled', true);
    config()->set('academy.live_sessions_enabled', true);

    $user   = xapiStudent();
    $course = xapiCourse();
    xapiEnroll($course, $user);

    $session = LiveSession::create([
        'course_id'  => $course->id,
        'title'      => 'Séance live',
        'provider'   => 'meet',
        'join_url'   => 'https://meet.google.com/xyz-abcd-efg',
        'starts_at'  => now()->addHour(),
        'ends_at'    => now()->addHours(2),
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(\Modules\Academy\Livewire\CourseLiveSessions::class, ['course' => $course])
        ->call('join', $session->id);

    $statement = XapiStatement::forUser($user->id)
        ->forVerb(XapiRecorderService::VERB_ATTENDED)
        ->forObject(XapiRecorderService::OBJECT_LIVE_SESSION, $session->id)
        ->first();

    expect($statement)->not->toBeNull();
    expect($statement->context['course_id'] ?? null)->toBe($course->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// (h) Fondation de lecture — statementsFor() est scopée à l'utilisateur (anti-IDOR)
// ─────────────────────────────────────────────────────────────────────────────

test('statementsFor() ne renvoie que les statements de l\'utilisateur demandé', function (): void {
    config()->set('academy.xapi_enabled', true);
    config()->set('academy.gamification_enabled', true);

    $alice = xapiStudent();
    $bob   = xapiStudent();
    $course = xapiCourse();

    app(GamificationService::class)->award($alice, $course, 'manual', 1, 'alice_xp', 10);
    app(GamificationService::class)->award($bob, $course, 'manual', 1, 'bob_xp', 20);

    $aliceStatements = app(XapiRecorderService::class)->statementsFor($alice)->get();

    expect($aliceStatements)->toHaveCount(1);
    expect($aliceStatements->first()->user_id)->toBe($alice->id);
});
