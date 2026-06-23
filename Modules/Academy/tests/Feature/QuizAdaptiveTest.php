<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - COMPORTEMENT ADAPTATIF (réessai d'une question avec PÉNALITÉ, type
 * Moodle « Adaptive mode »). Étend le mode immédiat (V1-f) : chaque question porte un
 * bouton « Vérifier » qui révèle juste/faux ; un échec NE verrouille PAS la question,
 * l'étudiant peut RÉESSAYER, chaque essai raté retranchant une pénalité.
 *
 * Prouve que :
 *  - PÉNALITÉ : correct au 1er essai = points pleins ; au 2e (1 raté, pénalité 1/3) =
 *    2/3 ; au 3e (2 ratés) = 1/3 ; jamais correct = 0 ; bornée >= 0 (jamais négative) ;
 *  - VERROUILLAGE serveur : au max d'essais, la question se verrouille ; un « Vérifier »
 *    supplémentaire est IGNORÉ (idempotent, borne anti-spam) ;
 *  - passing_score : un score PÉNALISÉ sous le seuil échoue ;
 *  - SÉCURITÉ : la justesse / la pénalité ne sont jamais envoyées par le client (un
 *    paramètre forgé est ignoré, le serveur seul décide) ; bonnes réponses non exposées
 *    avant « Vérifier » ni pendant le réessai ;
 *  - RÉTROCOMPAT : un item sans comportement reste différé (route verify refusée 404).
 *
 * Autonome : helpers préfixés f6. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\QuizBehaviour;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers f6 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function f6Course(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours adaptatif',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function f6Lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

function f6Owner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $owner->id,
        'role'      => 'owner',
    ]);

    return $owner;
}

function f6Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque adaptative',
        'position'  => 0,
    ]);
}

/** N affirmations vraies → questions vraifaux dont la bonne réponse est l'index 0 (Vrai). */
function f6FillTrueFalse(QuestionCategory $cat, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        Question::create([
            'category_id' => $cat->id,
            'owner_id'    => $cat->owner_id,
            'type'        => 'truefalse',
            'prompt'      => "Affirmation #$i (vraie)",
            'payload'     => ['answer' => true],
            'difficulty'  => 'facile',
            'is_active'   => true,
        ]);
    }
}

function f6QuizItem(Lesson $lesson, array $payload): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => $payload,
        'is_required' => false,
    ]);
}

function f6Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function f6Student(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function f6StartUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/start";
}

function f6SubmitUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/submit";
}

function f6VerifyUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/verify";
}

/**
 * Monte un quiz adaptatif d'UNE question vraifaux (index 0 = Vrai), démarre la session
 * et renvoie [course, lesson, item, student]. La banque a assez de questions tirées.
 */
function f6SetupSingle(string $slug, array $extra = []): array
{
    $course = f6Course($slug);
    $lesson = f6Lesson($course);
    $owner  = f6Owner($course);
    $cat    = f6Category($owner);
    f6FillTrueFalse($cat, 1);

    $item = f6QuizItem($lesson, array_merge([
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 1],
        'passing_score'      => 60,
        'question_behaviour' => 'adaptive',
    ], $extra));

    $student = f6Student();
    f6Enroll($course, $student);
    test()->actingAs($student)->post(f6StartUrl($course, $lesson, $item));

    return [$course, $lesson, $item, $student];
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. NORMALISATION : adaptive reconnu + lecture défensive penalty/maxTries
// ─────────────────────────────────────────────────────────────────────────────

test('QuizBehaviour : adaptive reconnu, penalty/maxTries défensifs (défauts 1/3 et 3)', function (): void {
    expect(QuizBehaviour::for(['question_behaviour' => 'adaptive']))->toBe('adaptive');
    expect(QuizBehaviour::isAdaptive(['question_behaviour' => 'adaptive']))->toBeTrue();
    expect(QuizBehaviour::isPerQuestion(['question_behaviour' => 'adaptive']))->toBeTrue();
    expect(QuizBehaviour::isPerQuestion(['question_behaviour' => 'immediate']))->toBeTrue();
    expect(QuizBehaviour::isPerQuestion(null))->toBeFalse();

    // Défauts (clé absente ou invalide).
    expect(QuizBehaviour::penaltyFor([]))->toBe(1 / 3);
    expect(QuizBehaviour::maxTriesFor([]))->toBe(3);
    expect(QuizBehaviour::penaltyFor(['adaptive_penalty' => 'xxx']))->toBe(1 / 3);
    expect(QuizBehaviour::maxTriesFor(['adaptive_max_tries' => 0]))->toBe(1);   // borné [1,10]
    expect(QuizBehaviour::maxTriesFor(['adaptive_max_tries' => 99]))->toBe(10);
    expect(QuizBehaviour::penaltyFor(['adaptive_penalty' => 2]))->toBe(1.0);    // borné [0,1]
    expect(QuizBehaviour::penaltyFor(['adaptive_penalty' => 0.5]))->toBe(0.5);

    // Formule (DRY) : multiplicateur borné >= 0.
    expect(QuizBehaviour::penaltyMultiplier(0, 1 / 3))->toBe(1.0);
    expect(QuizBehaviour::penaltyMultiplier(1, 1 / 3))->toEqualWithDelta(2 / 3, 0.0001);
    expect(QuizBehaviour::penaltyMultiplier(2, 1 / 3))->toEqualWithDelta(1 / 3, 0.0001);
    expect(QuizBehaviour::penaltyMultiplier(3, 0.5))->toBe(0.0); // 1 - 1.5 borné à 0
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. PÉNALITÉ : correct au n-ième essai → fraction des points
// ─────────────────────────────────────────────────────────────────────────────

test('correct au 1er essai → points pleins (100 %)', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-1er');

    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0]); // correct
    $this->actingAs($student)->post(f6SubmitUrl($c, $l, $i))->assertRedirect();

    $a = QuizAttempt::where('lesson_item_id', $i->id)->first();
    expect((int) $a->percent)->toBe(100);
    expect($a->passed)->toBeTrue();
});

test('correct au 2e essai (1 raté, pénalité 1/3) → 2/3 des points (67 %)', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-2e');

    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté
    // Pas encore verrouillée (max 3 par défaut).
    $v = session("academy.quiz.{$i->id}")['validated'][0];
    expect($v['locked'])->toBeFalse();
    expect($v['tries'])->toBe(1);

    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0]); // correct
    $v = session("academy.quiz.{$i->id}")['validated'][0];
    expect($v['locked'])->toBeTrue();
    expect($v['correct'])->toBeTrue();

    $this->actingAs($student)->post(f6SubmitUrl($c, $l, $i))->assertRedirect();
    $a = QuizAttempt::where('lesson_item_id', $i->id)->first();
    expect((int) $a->percent)->toBe(67); // round(2/3 * 100)
});

test('correct au 3e essai (2 ratés) → 1/3 des points (33 %)', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-3e');

    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté 1
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté 2
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0]); // correct

    $this->actingAs($student)->post(f6SubmitUrl($c, $l, $i))->assertRedirect();
    $a = QuizAttempt::where('lesson_item_id', $i->id)->first();
    expect((int) $a->percent)->toBe(33); // round(1/3 * 100)
});

test('jamais correct (3 essais ratés) → 0 % et verrouillage au max', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-jamais');

    for ($k = 0; $k < 3; $k++) {
        $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté
    }
    $v = session("academy.quiz.{$i->id}")['validated'][0];
    expect($v['locked'])->toBeTrue();   // verrouillée au max d'essais
    expect($v['correct'])->toBeFalse();
    expect($v['tries'])->toBe(3);

    $this->actingAs($student)->post(f6SubmitUrl($c, $l, $i))->assertRedirect();
    $a = QuizAttempt::where('lesson_item_id', $i->id)->first();
    expect((int) $a->percent)->toBe(0);
    expect($a->passed)->toBeFalse();
});

test('pénalité bornée >= 0 : pénalité 0,5 sur 3 essais ne donne jamais de points négatifs', function (): void {
    // max_tries 4 → 3 ratés possibles AVANT une réussite (pénalité 3 × 0,5 = 1,5 → bornée 0).
    [$c, $l, $i, $student] = f6SetupSingle('adapt-borne', [
        'adaptive_penalty'   => 0.5,
        'adaptive_max_tries' => 4,
    ]);

    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté 1
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté 2
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté 3
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0]); // correct (4e)

    $v = session("academy.quiz.{$i->id}")['validated'][0];
    expect($v['correct'])->toBeTrue();
    expect((float) $v['points_earned'])->toBe(0.0); // max(0, 1 - 3×0,5) = 0, jamais négatif

    $this->actingAs($student)->post(f6SubmitUrl($c, $l, $i))->assertRedirect();
    $a = QuizAttempt::where('lesson_item_id', $i->id)->first();
    expect((int) $a->percent)->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. VERROUILLAGE serveur : max_tries respecté (4e « Vérifier » ignoré)
// ─────────────────────────────────────────────────────────────────────────────

test('max_tries respecté serveur : un « Vérifier » sur une question verrouillée est ignoré', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-max'); // max 3 par défaut

    for ($k = 0; $k < 3; $k++) {
        $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté
    }
    $before = session("academy.quiz.{$i->id}")['validated'][0];
    expect($before['locked'])->toBeTrue();
    expect($before['tries'])->toBe(3);

    // 4e « Vérifier » (même avec la BONNE réponse) → IGNORÉ : la question reste verrouillée
    // sur son état final (faux, 3 essais). Le client ne peut pas dépasser max_tries.
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0]);
    $after = session("academy.quiz.{$i->id}")['validated'][0];
    expect($after['correct'])->toBeFalse();
    expect($after['tries'])->toBe(3);
    expect($after['locked'])->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. passing_score : un score pénalisé sous le seuil échoue
// ─────────────────────────────────────────────────────────────────────────────

test('passing_score : un score pénalisé sous le seuil fait échouer la tentative', function (): void {
    // Seuil 70 % ; réussite au 2e essai = 67 % (< 70) → échec.
    [$c, $l, $i, $student] = f6SetupSingle('adapt-seuil', ['passing_score' => 70]);

    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]); // raté
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0]); // correct

    $this->actingAs($student)->post(f6SubmitUrl($c, $l, $i))->assertRedirect();
    $a = QuizAttempt::where('lesson_item_id', $i->id)->first();
    expect((int) $a->percent)->toBe(67);
    expect($a->passed)->toBeFalse(); // 67 < 70
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. SÉCURITÉ : justesse / pénalité jamais envoyées par le client
// ─────────────────────────────────────────────────────────────────────────────

test('le client ne peut pas forger la justesse ni les points (paramètres ignorés)', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-forge');

    // Mauvaise réponse + « correct » et « points_earned » bidons → tout ignoré côté serveur.
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), [
        'index'         => 0,
        'answer'        => 1,      // faux
        'correct'       => 1,      // forgé
        'points_earned' => 999,    // forgé
        'tries'         => 0,      // forgé
        'locked'        => 1,      // forgé
    ]);

    $v = session("academy.quiz.{$i->id}")['validated'][0];
    expect($v['correct'])->toBeFalse();           // le serveur a noté faux
    expect($v['locked'])->toBeFalse();            // 1 raté, max 3 → pas verrouillée
    expect($v['tries'])->toBe(1);                 // décompte serveur
    expect((float) ($v['best_raw'] ?? 0))->toBe(0.0); // aucun point forgé retenu
});

test('les bonnes réponses ne sont pas exposées avant « Vérifier » ni pendant le réessai', function (): void {
    [$c, $l, $i, $student] = f6SetupSingle('adapt-noexpose');

    // Avant toute validation : bouton « Vérifier », aucune justesse révélée.
    $before = $this->actingAs($student)->get(route('academy.lessons.show', [$c, $l]))->getContent();
    expect($before)->toContain('Vérifier');
    expect($before)->not->toContain('✔ Bonne réponse');
    expect($before)->not->toContain('Bonne réponse :');

    // Un essai RATÉ : on annonce l'échec + le réessai, mais on NE révèle PAS la bonne réponse.
    $this->actingAs($student)->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 1]);
    $retry = $this->actingAs($student)->get(route('academy.lessons.show', [$c, $l]))->getContent();
    expect($retry)->toContain('Réessayer');
    expect($retry)->toContain('Réponse incorrecte');
    expect($retry)->not->toContain('Bonne réponse :'); // pas de révélation pendant le réessai
    expect($retry)->not->toContain('✔ Bonne réponse');
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. RÉTROCOMPAT : un item sans comportement reste différé (verify refusé)
// ─────────────────────────────────────────────────────────────────────────────

test('un item sans comportement reste différé : la route verify est refusée (404)', function (): void {
    $c   = f6Course('adapt-retro');
    $l   = f6Lesson($c);
    $o   = f6Owner($c);
    $cat = f6Category($o);
    f6FillTrueFalse($cat, 2);

    // AUCUNE clé question_behaviour → différé (rétrocompat stricte).
    $i = f6QuizItem($l, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 2],
        'passing_score' => 60,
    ]);
    expect(QuizBehaviour::isAdaptive($i->payload))->toBeFalse();
    expect(QuizBehaviour::isPerQuestion($i->payload))->toBeFalse();

    $student = f6Student();
    f6Enroll($c, $student);
    $this->actingAs($student)->post(f6StartUrl($c, $l, $i));

    $this->actingAs($student)
        ->post(f6VerifyUrl($c, $l, $i), ['index' => 0, 'answer' => 0])
        ->assertNotFound();
});
