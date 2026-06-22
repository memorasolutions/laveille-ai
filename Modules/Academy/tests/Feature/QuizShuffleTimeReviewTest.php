<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-d : MÉLANGE + LIMITE DE TEMPS + OPTIONS DE RÉVISION.
 *
 * Prouve que :
 *  - MÉLANGE DES RÉPONSES : QuizService::shuffleRound remappe l'index `correct`
 *    (et choice_feedback) → le scoring (QuizService::score) reste EXACT (100 %) ;
 *  - MÉLANGE DES QUESTIONS : l'ENSEMBLE des questions est préservé (aucune perte
 *    ni duplication), l'ordre peut changer ;
 *  - LIMITE DE TEMPS : la garde SERVEUR accepte une soumission tardive et la
 *    marque timed_out=true ; sans limite → timed_out=false (inchangé) ;
 *  - OPTIONS DE RÉVISION : show_right_answer=false masque la bonne réponse ;
 *    défauts (toutes true) = révision V1-a complète inchangée ;
 *  - RÉTROCOMPAT : un item sans ces clés → aucun mélange, aucune limite, révision
 *    complète.
 *
 * Autonome : helpers préfixés v1d. SKIPPED si Academy off.
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
use Modules\Academy\Services\QuizReviewOptions;
use Modules\Academy\Services\QuizService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers v1d (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v1dCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V1-d',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v1dLesson(Course $course): Lesson
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

function v1dOwner(Course $course): User
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

function v1dCategory(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque V1-d',
        'position'  => 0,
    ]);
}

function v1dFillTrueFalse(QuestionCategory $cat, int $n): void
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

function v1dQuizItem(Lesson $lesson, array $payload): LessonItem
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

function v1dEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v1dStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function v1dStartUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/start";
}

function v1dSubmitUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/submit";
}

/** Construit un round de N qcm, bonne réponse = index 0, avec choice_feedback. */
function v1dQcmRound(int $n): array
{
    $round = [];
    for ($q = 0; $q < $n; $q++) {
        $round[] = [
            'type'            => 'qcm',
            'question'        => "Question $q",
            'choices'         => ["Bonne $q", "Mauvaise A $q", "Mauvaise B $q", "Mauvaise C $q"],
            'correct'         => 0,
            'choice_feedback' => [0 => 'Exact', 1 => 'Non A', 2 => 'Non B', 3 => 'Non C'],
        ];
    }

    return $round;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. MÉLANGE DES RÉPONSES : le scoring N'EST PAS cassé (correct remappé)
// ─────────────────────────────────────────────────────────────────────────────

test('le mélange des réponses remappe correct et le feedback (scoring exact)', function (): void {
    $round = v1dQcmRound(5);

    $shuffled = QuizService::shuffleRound($round, false, true);

    // Pour chaque question : le choix d'index `correct` doit être "Bonne X" et le
    // choice_feedback à cet index doit être "Exact" (cohérence V1-a après permutation).
    foreach ($shuffled as $q => $question) {
        $correctIdx = $question['correct'];
        expect($question['choices'][$correctIdx])->toBe("Bonne $q");
        expect($question['choice_feedback'][$correctIdx])->toBe('Exact');
    }

    // Répondre selon l'ordre du round mélangé → 100 % (scoring inchangé).
    $answers = [];
    foreach ($shuffled as $i => $question) {
        $answers[(string) $i] = $question['correct'];
    }

    $score = QuizService::score($shuffled, $answers);
    expect($score['percent'])->toBe(100);
    expect($score['correct'])->toBe(5);
});

test('mélanger les réponses préserve l ensemble des choix de chaque question', function (): void {
    $round    = v1dQcmRound(1);
    $shuffled = QuizService::shuffleRound($round, false, true);

    sort($round[0]['choices']);
    $got = $shuffled[0]['choices'];
    sort($got);

    expect($got)->toBe($round[0]['choices']);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. MÉLANGE DES QUESTIONS : ensemble préservé (aucune perte/duplication)
// ─────────────────────────────────────────────────────────────────────────────

test('le mélange des questions préserve l ensemble (mêmes questions, ordre libre)', function (): void {
    $round    = v1dQcmRound(6);
    $shuffled = QuizService::shuffleRound($round, true, false);

    expect(count($shuffled))->toBe(6);

    $before = collect($round)->pluck('question')->sort()->values()->all();
    $after  = collect($shuffled)->pluck('question')->sort()->values()->all();
    expect($after)->toBe($before);

    // Index séquentiels 0..5 (les réponses sont indexées par numéro de question).
    expect(array_keys($shuffled))->toBe(range(0, 5));
});

test('le scoring reste exact après mélange combiné (questions + réponses)', function (): void {
    $round    = v1dQcmRound(4);
    $shuffled = QuizService::shuffleRound($round, true, true);

    $answers = [];
    foreach ($shuffled as $i => $question) {
        $answers[(string) $i] = $question['correct'];
    }

    expect(QuizService::score($shuffled, $answers)['percent'])->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. LIMITE DE TEMPS : garde serveur (soumission tardive marquée timed_out)
// ─────────────────────────────────────────────────────────────────────────────

test('une soumission au-delà de la limite est acceptée et marquée timed_out', function (): void {
    $course = v1dCourse('cours-temps');
    $lesson = v1dLesson($course);
    $owner  = v1dOwner($course);
    $cat    = v1dCategory($owner);
    v1dFillTrueFalse($cat, 4);

    $item = v1dQuizItem($lesson, [
        'question_bank'      => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score'      => 60,
        'time_limit_minutes' => 5,
    ]);

    $student = v1dStudent();
    v1dEnroll($course, $student);

    // Démarre le round, puis fabrique un started_at vieux de 10 min (> limite 5 min).
    $this->actingAs($student)->post(v1dStartUrl($course, $lesson, $item));

    $key  = "academy.quiz.{$item->id}";
    $data = session($key);
    $data['started_at'] = now()->subMinutes(10)->toIso8601String();
    session([$key => $data]);

    $answers = [];
    foreach ($data['questions'] as $i => $q) {
        $answers[(string) $i] = 0; // toutes "Vrai"
    }

    $this->actingAs($student)
        ->post(v1dSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->timed_out)->toBeTrue();
    // La soumission est ACCEPTÉE (Moodle-like) : le score est bien calculé.
    expect($attempt->percent)->toBe(100);
});

test('sans limite de temps la tentative n est jamais marquée timed_out', function (): void {
    $course = v1dCourse('cours-sans-temps');
    $lesson = v1dLesson($course);
    $owner  = v1dOwner($course);
    $cat    = v1dCategory($owner);
    v1dFillTrueFalse($cat, 4);

    $item = v1dQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = v1dStudent();
    v1dEnroll($course, $student);

    $this->actingAs($student)->post(v1dStartUrl($course, $lesson, $item));

    // Même un started_at ancien ne marque rien (aucune limite définie).
    $key  = "academy.quiz.{$item->id}";
    $data = session($key);
    $data['started_at'] = now()->subHours(2)->toIso8601String();
    session([$key => $data]);

    $answers = [];
    foreach ($data['questions'] as $i => $q) {
        $answers[(string) $i] = 0;
    }

    $this->actingAs($student)
        ->post(v1dSubmitUrl($course, $lesson, $item), ['answers' => $answers]);

    $attempt = QuizAttempt::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->first();

    expect($attempt->timed_out)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. OPTIONS DE RÉVISION (helper) + défauts
// ─────────────────────────────────────────────────────────────────────────────

test('les options de révision par défaut sont toutes vraies (V1-a inchangé)', function (): void {
    $opts = QuizReviewOptions::normalize(null);

    foreach (QuizReviewOptions::KEYS as $key) {
        expect($opts[$key])->toBeTrue();
    }
});

test('show_right_answer=false est respecté et les autres restent vrais par défaut', function (): void {
    $opts = QuizReviewOptions::normalize(['show_right_answer' => false]);

    expect($opts['show_right_answer'])->toBeFalse();
    expect($opts['show_correctness'])->toBeTrue();
    expect($opts['show_general_feedback'])->toBeTrue();
    expect(QuizReviewOptions::show(['show_right_answer' => false], 'show_right_answer'))->toBeFalse();
});

test('la révision ne montre pas la bonne réponse quand show_right_answer=false', function (): void {
    $course = v1dCourse('cours-review');
    $lesson = v1dLesson($course);
    $owner  = v1dOwner($course);
    $cat    = v1dCategory($owner);
    v1dFillTrueFalse($cat, 3);

    $item = v1dQuizItem($lesson, [
        'question_bank'  => ['category_id' => $cat->id, 'draw_count' => 3],
        'passing_score'  => 60,
        'review_options' => ['show_right_answer' => false],
    ]);

    $student = v1dStudent();
    v1dEnroll($course, $student);

    $this->actingAs($student)->post(v1dStartUrl($course, $lesson, $item));

    // Réponses TOUTES FAUSSES (index 1) → la "bonne réponse" serait normalement affichée.
    $round   = session("academy.quiz.{$item->id}")['questions'];
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 1;
    }

    $this->actingAs($student)
        ->post(v1dSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    // Rendu de la leçon : la section révision ne doit pas exposer « Bonne réponse : ».
    $html = $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->getContent();

    expect($html)->not->toContain('Bonne réponse :');
    // La justesse reste affichée (toggle par défaut true).
    expect($html)->toContain('À revoir');
});

test('par défaut (toutes options) la révision expose la bonne réponse', function (): void {
    $course = v1dCourse('cours-review-full');
    $lesson = v1dLesson($course);
    $owner  = v1dOwner($course);
    $cat    = v1dCategory($owner);
    v1dFillTrueFalse($cat, 3);

    $item = v1dQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 3],
        'passing_score' => 60,
    ]);

    $student = v1dStudent();
    v1dEnroll($course, $student);

    $this->actingAs($student)->post(v1dStartUrl($course, $lesson, $item));

    $round   = session("academy.quiz.{$item->id}")['questions'];
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 1; // fausses
    }

    $this->actingAs($student)
        ->post(v1dSubmitUrl($course, $lesson, $item), ['answers' => $answers]);

    $html = $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->getContent();

    expect($html)->toContain('Bonne réponse :');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. RÉTROCOMPAT : item sans clés V1-d → aucun mélange, pas de timer, révision pleine
// ─────────────────────────────────────────────────────────────────────────────

test('un item sans clés V1-d ne mélange pas et reste sans limite', function (): void {
    $round = v1dQcmRound(4);

    // shuffleRound n'est jamais appelé par le contrôleur quand les flags sont absents,
    // mais on prouve aussi que false/false est un no-op fidèle.
    $noop = QuizService::shuffleRound($round, false, false);
    expect($noop)->toBe($round);

    $course = v1dCourse('cours-retrocompat');
    $lesson = v1dLesson($course);
    $owner  = v1dOwner($course);
    $cat    = v1dCategory($owner);
    v1dFillTrueFalse($cat, 4);

    $item = v1dQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = v1dStudent();
    v1dEnroll($course, $student);

    $this->actingAs($student)->post(v1dStartUrl($course, $lesson, $item));
    $answers = [];
    foreach (session("academy.quiz.{$item->id}")['questions'] as $i => $q) {
        $answers[(string) $i] = 0;
    }

    $this->actingAs($student)
        ->post(v1dSubmitUrl($course, $lesson, $item), ['answers' => $answers]);

    $attempt = QuizAttempt::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->first();

    expect($attempt->timed_out)->toBeFalse();
    expect($attempt->percent)->toBe(100);
});
