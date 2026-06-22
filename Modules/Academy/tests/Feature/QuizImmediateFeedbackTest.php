<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-f : COMPORTEMENT DE QUESTION (rétroaction IMMÉDIATE) en complément
 * du mode différé actuel (Moodle « how questions behave »).
 *
 * Prouve que :
 *  - DÉFÉRÉ (défaut) : un item SANS la clé reste en mode différé (1 soumission,
 *    révision à la fin) ; la route de validation par question est REFUSÉE (404) ;
 *  - NORMALISATION : QuizBehaviour::for/isImmediate (liste blanche, défaut deferred) ;
 *  - IMMÉDIAT : valider une question la SCORE SERVEUR, révèle sa justesse et la
 *    VERROUILLE (idempotent) ; la bonne réponse n'est pas exposée AVANT validation ;
 *  - SCORE FINAL : à la complétion, le score = celui qu'aurait donné une soumission
 *    différée des MÊMES réponses (chemin de scoring identique) ;
 *  - attempts_allowed : un parcours immédiat complet = UNE tentative (limite honorée) ;
 *  - SÉCURITÉ : non-inscrit/anonyme rejeté ; le client ne peut pas forger « correct »
 *    (le serveur décide) ; un index hors-round est rejeté (404).
 *
 * Autonome : helpers préfixés v1f. SKIPPED si Academy off.
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
// Helpers v1f (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v1fCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V1-f',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v1fLesson(Course $course): Lesson
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

function v1fOwner(Course $course): User
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

function v1fCategory(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque V1-f',
        'position'  => 0,
    ]);
}

/** N affirmations vraies → questions vraifaux dont la bonne réponse est l'index 0 (Vrai). */
function v1fFillTrueFalse(QuestionCategory $cat, int $n): void
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

function v1fQuizItem(Lesson $lesson, array $payload): LessonItem
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

function v1fEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v1fStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function v1fStartUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/start";
}

function v1fSubmitUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/submit";
}

function v1fVerifyUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/verify";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. NORMALISATION du comportement (liste blanche, défaut deferred)
// ─────────────────────────────────────────────────────────────────────────────

test('QuizBehaviour : défaut deferred, immediate reconnu, valeur inconnue retombe sur deferred', function (): void {
    expect(QuizBehaviour::for(null))->toBe('deferred');
    expect(QuizBehaviour::for([]))->toBe('deferred');
    expect(QuizBehaviour::for(['question_behaviour' => 'immediate']))->toBe('immediate');
    expect(QuizBehaviour::for(['question_behaviour' => 'adaptive']))->toBe('deferred'); // non actif
    expect(QuizBehaviour::for(['question_behaviour' => 'n_importe_quoi']))->toBe('deferred');

    expect(QuizBehaviour::isImmediate(['question_behaviour' => 'immediate']))->toBeTrue();
    expect(QuizBehaviour::isImmediate(null))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. RÉTROCOMPAT : mode différé (défaut) strictement inchangé
// ─────────────────────────────────────────────────────────────────────────────

test('un item sans la clé reste en mode différé (1 soumission) et la route verify est refusée', function (): void {
    $course = v1fCourse('cours-deferred');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 3);

    $item = v1fQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 3],
        'passing_score' => 60,
    ]);

    expect(QuizBehaviour::isImmediate($item->payload))->toBeFalse();

    $student = v1fStudent();
    v1fEnroll($course, $student);

    $this->actingAs($student)->post(v1fStartUrl($course, $lesson, $item));

    // La validation par question N'EXISTE PAS en différé → 404 (route gardée).
    $round = session("academy.quiz.{$item->id}")['questions'];
    $this->actingAs($student)
        ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 0])
        ->assertNotFound();

    // Soumission différée classique : toutes les réponses d'un coup → score normal.
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 0; // toutes "Vrai" → correctes
    }
    $this->actingAs($student)
        ->post(v1fSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    expect($attempt)->not->toBeNull();
    expect($attempt->percent)->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. IMMÉDIAT : valider une question la score SERVEUR, révèle et verrouille
// ─────────────────────────────────────────────────────────────────────────────

test('valider une question la score serveur, révèle la justesse et la verrouille (idempotent)', function (): void {
    $course = v1fCourse('cours-immediate');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 3);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 3],
        'passing_score'      => 60,
        'question_behaviour' => 'immediate',
    ]);

    $student = v1fStudent();
    v1fEnroll($course, $student);

    $this->actingAs($student)->post(v1fStartUrl($course, $lesson, $item));

    // AVANT validation : la page ne révèle pas la justesse / la bonne réponse, et
    // propose le bouton « Vérifier ».
    $htmlBefore = $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->getContent();
    expect($htmlBefore)->toContain('Vérifier');
    expect($htmlBefore)->not->toContain('✔ Bonne réponse');
    expect($htmlBefore)->not->toContain('Bonne réponse :');

    // Valider la question 0 avec la BONNE réponse (index 0 = Vrai).
    $this->actingAs($student)
        ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 0])
        ->assertRedirect();

    $validated = session("academy.quiz.{$item->id}")['validated'];
    expect($validated)->toHaveKey(0);
    expect($validated[0]['correct'])->toBeTrue();
    expect($validated[0]['answer'])->toBe(0);

    // La page révèle alors la justesse de la question validée.
    $htmlAfter = $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->getContent();
    expect($htmlAfter)->toContain('✔ Bonne réponse');

    // IDEMPOTENT / anti-triche : re-valider la même question avec une AUTRE réponse
    // ne change rien (la question reste verrouillée sur la 1re validation).
    $this->actingAs($student)
        ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 1]);
    $validated = session("academy.quiz.{$item->id}")['validated'];
    expect($validated[0]['answer'])->toBe(0);
    expect($validated[0]['correct'])->toBeTrue();
});

test('le client ne peut pas forger la justesse : une mauvaise réponse est notée fausse par le serveur', function (): void {
    $course = v1fCourse('cours-forge');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 2);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 2],
        'question_behaviour' => 'immediate',
    ]);

    $student = v1fStudent();
    v1fEnroll($course, $student);
    $this->actingAs($student)->post(v1fStartUrl($course, $lesson, $item));

    // Mauvaise réponse (index 1 = Faux) + paramètre « correct » bidon ignoré.
    $this->actingAs($student)
        ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 1, 'correct' => 1]);

    $validated = session("academy.quiz.{$item->id}")['validated'];
    expect($validated[0]['correct'])->toBeFalse();
});

test('un index hors du round est rejeté (404)', function (): void {
    $course = v1fCourse('cours-idx');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 2);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 2],
        'question_behaviour' => 'immediate',
    ]);

    $student = v1fStudent();
    v1fEnroll($course, $student);
    $this->actingAs($student)->post(v1fStartUrl($course, $lesson, $item));

    $this->actingAs($student)
        ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 99, 'answer' => 0])
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. SCORE FINAL : identique au chemin différé sur les mêmes réponses
// ─────────────────────────────────────────────────────────────────────────────

test('le score final immédiat = somme des bonnes réponses (chemin de scoring identique)', function (): void {
    $course = v1fCourse('cours-score');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 4);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score'      => 60,
        'question_behaviour' => 'immediate',
    ]);

    $student = v1fStudent();
    v1fEnroll($course, $student);
    $this->actingAs($student)->post(v1fStartUrl($course, $lesson, $item));

    $round = session("academy.quiz.{$item->id}")['questions'];
    $n     = count($round);
    expect($n)->toBe(4);

    // 3 bonnes (index 0 = Vrai) + 1 mauvaise (index 1) → 75 %.
    foreach ($round as $i => $q) {
        $answer = ($i === $n - 1) ? 1 : 0;
        $this->actingAs($student)
            ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => $i, 'answer' => $answer]);
    }

    // « Terminer » ne porte AUCUNE réponse : le serveur lit la session verrouillée.
    $this->actingAs($student)
        ->post(v1fSubmitUrl($course, $lesson, $item))
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    expect($attempt)->not->toBeNull();
    // Colonne `score` = NB de bonnes réponses (cf. QuizController::submitQuiz) ;
    // `max_score` = NB de questions ; `percent` pondéré (ici tous points=1 → 3/4).
    expect((int) $attempt->score)->toBe(3);
    expect((int) $attempt->max_score)->toBe(4);
    expect((int) $attempt->percent)->toBe(75);
    expect($attempt->passed)->toBeTrue(); // 75 >= 60
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. attempts_allowed : un parcours immédiat complet = UNE tentative
// ─────────────────────────────────────────────────────────────────────────────

test('attempts_allowed est honoré en immédiat (un parcours complet = une tentative)', function (): void {
    $course = v1fCourse('cours-att');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 2);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 2],
        'attempts_allowed'   => 1,
        'question_behaviour' => 'immediate',
    ]);

    $student = v1fStudent();
    v1fEnroll($course, $student);

    // 1er parcours complet : start → verify chaque question → submit.
    $this->actingAs($student)->post(v1fStartUrl($course, $lesson, $item));
    foreach (session("academy.quiz.{$item->id}")['questions'] as $i => $q) {
        $this->actingAs($student)->post(v1fVerifyUrl($course, $lesson, $item), ['index' => $i, 'answer' => 0]);
    }
    $this->actingAs($student)->post(v1fSubmitUrl($course, $lesson, $item));

    expect(QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->count())->toBe(1);

    // 2e démarrage refusé (limite atteinte) → aucune session de quiz recréée.
    $this->actingAs($student)
        ->post(v1fStartUrl($course, $lesson, $item))
        ->assertRedirect();
    expect(session()->has("academy.quiz.{$item->id}"))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. SÉCURITÉ : anonyme / non-inscrit rejetés
// ─────────────────────────────────────────────────────────────────────────────

test('un anonyme ne peut pas valider une question (auth requise)', function (): void {
    $course = v1fCourse('cours-anon');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 2);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 2],
        'question_behaviour' => 'immediate',
    ]);

    // Pas d'auth → la route auth redirige (login), aucune validation possible.
    $this->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 0])
        ->assertRedirect();
});

test('un inscrit-non ne peut pas valider une question (403)', function (): void {
    $course = v1fCourse('cours-noenroll');
    $lesson = v1fLesson($course);
    $owner  = v1fOwner($course);
    $cat    = v1fCategory($owner);
    v1fFillTrueFalse($cat, 2);

    $item = v1fQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 2],
        'question_behaviour' => 'immediate',
    ]);

    $intrus = v1fStudent(); // NON inscrit au cours
    $this->actingAs($intrus)
        ->post(v1fVerifyUrl($course, $lesson, $item), ['index' => 0, 'answer' => 0])
        ->assertForbidden();
});
