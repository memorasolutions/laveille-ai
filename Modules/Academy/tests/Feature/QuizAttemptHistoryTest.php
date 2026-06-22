<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-b HISTORIQUE DES TENTATIVES de quiz.
 *
 * Prouve que :
 *  - une soumission crée 1 QuizAttempt (score/max/percent/passed/answers/course_id) ;
 *  - la Completion de complétion reste posée si réussi (comportement INCHANGÉ) ;
 *  - plusieurs soumissions = plusieurs lignes (1 par tentative) ;
 *  - attempts_allowed s'applique désormais via le compte des QuizAttempt ;
 *  - un item sans attempts_allowed est illimité ;
 *  - (UI) l'indicateur « Tentative N » est présent pour l'inscrit.
 *
 * Autonome : helpers préfixés v1b (aucune redéclaration). SKIPPED si Academy off.
 * On s'appuie sur la banque de questions (truefalse) pour produire un round
 * déterministe et testable, sans dépendre de QtService (banque globale figée).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuizAttempt;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers v1b (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v1bCourse(string $slug = 'cours-v1b'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V1-b',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v1bLesson(Course $course): Lesson
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

function v1bOwner(Course $course): User
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

function v1bCategory(User $owner, string $name = 'Banque V1-b'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => $name,
        'position'  => 0,
    ]);
}

/** Crée N questions truefalse (réponse = Vrai) actives dans une catégorie. */
function v1bFillTrueFalse(QuestionCategory $cat, int $n): void
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

function v1bQuizItem(Lesson $lesson, array $payload): LessonItem
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

function v1bEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v1bStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function v1bStartUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
}

function v1bSubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";
}

/** Réponses « toutes Vrai » (= index 0) pour le round courant en session. */
function v1bAllCorrect(LessonItem $item): array
{
    $round   = session("academy.quiz.{$item->id}")['questions'] ?? [];
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 0;
    }

    return $answers;
}

/** Réponses « toutes Faux » (= index 1). */
function v1bAllWrong(LessonItem $item): array
{
    $round   = session("academy.quiz.{$item->id}")['questions'] ?? [];
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 1;
    }

    return $answers;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Une soumission crée 1 QuizAttempt avec les bons champs + Completion intacte
// ─────────────────────────────────────────────────────────────────────────────

test('une soumission réussie crée 1 QuizAttempt et pose la Completion', function (): void {
    $course = v1bCourse('cours-attempt-1');
    $lesson = v1bLesson($course);
    $owner  = v1bOwner($course);
    $cat    = v1bCategory($owner);
    v1bFillTrueFalse($cat, 4);

    $item = v1bQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = v1bStudent();
    v1bEnroll($course, $student);

    $this->actingAs($student)->post(v1bStartUrl($course, $lesson, $item));
    $answers = v1bAllCorrect($item);

    $this->actingAs($student)
        ->post(v1bSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->score)->toBe(4);
    expect($attempt->max_score)->toBe(4);
    expect($attempt->percent)->toBe(100);
    expect($attempt->passed)->toBeTrue();
    expect($attempt->course_id)->toBe($course->id);
    expect($attempt->answers)->toBe($answers);
    expect($attempt->submitted_at)->not->toBeNull();

    // Comportement INCHANGÉ : la complétion est posée si réussi.
    $completion = Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->first();
    expect($completion)->not->toBeNull();
});

test('une soumission échouée crée 1 QuizAttempt passed=false SANS Completion completed', function (): void {
    $course = v1bCourse('cours-attempt-fail');
    $lesson = v1bLesson($course);
    $owner  = v1bOwner($course);
    $cat    = v1bCategory($owner);
    v1bFillTrueFalse($cat, 4);

    $item = v1bQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = v1bStudent();
    v1bEnroll($course, $student);

    $this->actingAs($student)->post(v1bStartUrl($course, $lesson, $item));
    $answers = v1bAllWrong($item);

    $this->actingAs($student)
        ->post(v1bSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->passed)->toBeFalse();
    expect($attempt->percent)->toBe(0);

    // Aucune complétion 'completed' (échec).
    $completed = Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists();
    expect($completed)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Plusieurs soumissions = plusieurs lignes (1 par tentative)
// ─────────────────────────────────────────────────────────────────────────────

test('plusieurs soumissions créent plusieurs QuizAttempt', function (): void {
    $course = v1bCourse('cours-multi');
    $lesson = v1bLesson($course);
    $owner  = v1bOwner($course);
    $cat    = v1bCategory($owner);
    v1bFillTrueFalse($cat, 4);

    // Pas de attempts_allowed → illimité.
    $item = v1bQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = v1bStudent();
    v1bEnroll($course, $student);

    // 3 tentatives échouées de suite.
    for ($n = 0; $n < 3; $n++) {
        $this->actingAs($student)->post(v1bStartUrl($course, $lesson, $item));
        $this->actingAs($student)
            ->post(v1bSubmitUrl($course, $lesson, $item), ['answers' => v1bAllWrong($item)]);
    }

    $count = QuizAttempt::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->count();

    expect($count)->toBe(3);
    expect(QuizAttempt::attemptCount($student->id, $item->id))->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. attempts_allowed s'applique via le compte des QuizAttempt
// ─────────────────────────────────────────────────────────────────────────────

test('attempts_allowed bloque la 3e tentative quand allowed=2', function (): void {
    $course = v1bCourse('cours-limit');
    $lesson = v1bLesson($course);
    $owner  = v1bOwner($course);
    $cat    = v1bCategory($owner);
    v1bFillTrueFalse($cat, 4);

    $item = v1bQuizItem($lesson, [
        'question_bank'    => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score'    => 60,
        'attempts_allowed' => 2,
    ]);

    $student = v1bStudent();
    v1bEnroll($course, $student);

    // Tentative 1 + 2 (échouées) → 2 QuizAttempt.
    for ($n = 0; $n < 2; $n++) {
        $this->actingAs($student)->post(v1bStartUrl($course, $lesson, $item));
        $this->actingAs($student)
            ->post(v1bSubmitUrl($course, $lesson, $item), ['answers' => v1bAllWrong($item)]);
    }

    expect(QuizAttempt::attemptCount($student->id, $item->id))->toBe(2);

    // 3e démarrage → refusé (message inchangé), aucune nouvelle session de quiz.
    $this->actingAs($student)
        ->post(v1bStartUrl($course, $lesson, $item))
        ->assertSessionHas('error', 'Nombre de tentatives maximum atteint.');

    expect(session()->has("academy.quiz.{$item->id}"))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Sans attempts_allowed → illimité
// ─────────────────────────────────────────────────────────────────────────────

test('sans attempts_allowed le quiz est illimité', function (): void {
    $course = v1bCourse('cours-illimite');
    $lesson = v1bLesson($course);
    $owner  = v1bOwner($course);
    $cat    = v1bCategory($owner);
    v1bFillTrueFalse($cat, 4);

    $item = v1bQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = v1bStudent();
    v1bEnroll($course, $student);

    // 5 tentatives sans jamais être bloqué.
    for ($n = 0; $n < 5; $n++) {
        $this->actingAs($student)
            ->post(v1bStartUrl($course, $lesson, $item))
            ->assertRedirect();
        // Pas d'erreur de limite.
        expect(session('error'))->not->toBe('Nombre de tentatives maximum atteint.');
        $this->actingAs($student)
            ->post(v1bSubmitUrl($course, $lesson, $item), ['answers' => v1bAllWrong($item)]);
    }

    expect(QuizAttempt::attemptCount($student->id, $item->id))->toBe(5);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. (UI) indicateur « Tentative N » présent pour l'inscrit
// ─────────────────────────────────────────────────────────────────────────────

test('le lecteur affiche l’indicateur « Tentative N » pour l’inscrit', function (): void {
    $course = v1bCourse('cours-ui');
    $lesson = v1bLesson($course);
    $owner  = v1bOwner($course);
    $cat    = v1bCategory($owner);
    v1bFillTrueFalse($cat, 4);

    $item = v1bQuizItem($lesson, [
        'question_bank'    => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score'    => 60,
        'attempts_allowed' => 3,
    ]);

    $student = v1bStudent();
    v1bEnroll($course, $student);

    $this->actingAs($student)
        ->get("/academie/courses/{$course->slug}/lessons/{$lesson->id}")
        ->assertOk()
        ->assertSee('Tentative')
        ->assertSee('/ <strong>3</strong>', false);
});
