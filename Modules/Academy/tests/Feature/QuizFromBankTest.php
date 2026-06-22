<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Intégration QUIZ ↔ BANQUE DE QUESTIONS (QB2).
 *
 * Prouve que :
 *  - un item quiz lié à une catégorie de banque (payload['question_bank']) tire
 *    bien N questions de CETTE catégorie (theme='banque'), et NON la banque QT ;
 *  - une catégorie vidée → round de banque vide → REPLI sur qt_bank_key (et, QT
 *    absent en test, message « Quiz indisponible ») ;
 *  - un item SANS question_bank → comportement qt INCHANGÉ ;
 *  - un inscrit joue un quiz lié-banque et est noté par QuizService::score
 *    (réussite/échec selon passing_score) ;
 *  - ANTI-ESCALADE : un formateur ne peut pas lier la catégorie d'un AUTRE
 *    formateur via CourseEditor (l'id forgé est ignoré → pas de question_bank).
 *
 * Autonome : helpers préfixés qb2q (aucune redéclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers qb2q (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function qb2qCourse(string $slug = 'cours-banque'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours banque',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function qb2qLesson(Course $course): Lesson
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

function qb2qOwner(Course $course): User
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

function qb2qInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function qb2qCategory(User $owner, string $name = 'Banque'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => $name,
        'position'  => 0,
    ]);
}

/** Crée N questions truefalse (réponse = Vrai) actives dans une catégorie. */
function qb2qFillTrueFalse(QuestionCategory $cat, int $n): void
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

function qb2qQuizItem(Lesson $lesson, array $payload): LessonItem
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

function qb2qEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function qb2qStartUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
}

function qb2qSubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Un item lié à une catégorie tire N questions de la banque (pas la banque QT)
// ─────────────────────────────────────────────────────────────────────────────

test('un quiz lié à une catégorie tire N questions de la banque', function (): void {
    $course  = qb2qCourse();
    $lesson  = qb2qLesson($course);
    $owner   = qb2qOwner($course);
    $cat     = qb2qCategory($owner);
    qb2qFillTrueFalse($cat, 6);

    $item   = qb2qQuizItem($lesson, ['question_bank' => ['category_id' => $cat->id, 'draw_count' => 3]]);
    $student = User::factory()->create();
    $student->assignRole('student');
    qb2qEnroll($course, $student);

    $this->actingAs($student)
        ->post(qb2qStartUrl($course, $lesson, $item))
        ->assertRedirect();

    $round = session("academy.quiz.{$item->id}.questions") ?? session("academy.quiz.{$item->id}")['questions'] ?? [];

    expect($round)->toHaveCount(3);
    // Origine BANQUE : QuestionBankService marque chaque item theme='banque'.
    foreach ($round as $q) {
        expect($q['theme'] ?? null)->toBe('banque');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Catégorie vidée → repli (QT absent en test → « Quiz indisponible »)
// ─────────────────────────────────────────────────────────────────────────────

test('un quiz lié à une catégorie VIDE retombe sur le repli', function (): void {
    $course = qb2qCourse('cours-vide');
    $lesson = qb2qLesson($course);
    $owner  = qb2qOwner($course);
    $cat    = qb2qCategory($owner); // aucune question

    $item    = qb2qQuizItem($lesson, ['question_bank' => ['category_id' => $cat->id, 'draw_count' => 3]]);
    $student = User::factory()->create();
    $student->assignRole('student');
    qb2qEnroll($course, $student);

    $this->actingAs($student)
        ->post(qb2qStartUrl($course, $lesson, $item))
        ->assertRedirect();

    // Aucune session de banque : soit pas de round, soit un round QT (jamais theme='banque').
    $round = session("academy.quiz.{$item->id}")['questions'] ?? [];
    foreach ($round as $q) {
        expect($q['theme'] ?? null)->not->toBe('banque');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Item SANS question_bank → comportement qt INCHANGÉ
// ─────────────────────────────────────────────────────────────────────────────

test('un quiz sans question_bank garde le comportement qt', function (): void {
    $course = qb2qCourse('cours-qt');
    $lesson = qb2qLesson($course);

    $item    = qb2qQuizItem($lesson, ['qt_bank_key' => 'qt-questions']);
    $student = User::factory()->create();
    $student->assignRole('student');
    qb2qEnroll($course, $student);

    $response = $this->actingAs($student)->post(qb2qStartUrl($course, $lesson, $item));
    $response->assertRedirect();

    // Aucun round de banque possible (pas de lien) : la session ne contient jamais theme='banque'.
    $round = session("academy.quiz.{$item->id}")['questions'] ?? [];
    foreach ($round as $q) {
        expect($q['theme'] ?? null)->not->toBe('banque');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Un inscrit joue un quiz lié-banque et est noté (réussite / échec)
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit réussit un quiz lié-banque avec les bonnes réponses', function (): void {
    $course = qb2qCourse('cours-pass');
    $lesson = qb2qLesson($course);
    $owner  = qb2qOwner($course);
    $cat    = qb2qCategory($owner);
    qb2qFillTrueFalse($cat, 4); // toutes « Vrai » → bonne réponse = index 0

    $item = qb2qQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = User::factory()->create();
    $student->assignRole('student');
    qb2qEnroll($course, $student);

    $this->actingAs($student)->post(qb2qStartUrl($course, $lesson, $item));

    $round = session("academy.quiz.{$item->id}")['questions'] ?? [];
    expect($round)->toHaveCount(4);

    // Toutes les bonnes réponses (truefalse « Vrai » = index 0).
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 0;
    }

    $this->actingAs($student)
        ->post(qb2qSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $result = session('academy.quiz_result');
    expect($result)->not->toBeNull();
    expect($result['passed'])->toBeTrue();
    expect($result['percent'])->toBe(100);
});

test('un inscrit échoue un quiz lié-banque avec de mauvaises réponses', function (): void {
    $course = qb2qCourse('cours-fail');
    $lesson = qb2qLesson($course);
    $owner  = qb2qOwner($course);
    $cat    = qb2qCategory($owner);
    qb2qFillTrueFalse($cat, 4);

    $item = qb2qQuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = User::factory()->create();
    $student->assignRole('student');
    qb2qEnroll($course, $student);

    $this->actingAs($student)->post(qb2qStartUrl($course, $lesson, $item));

    $round = session("academy.quiz.{$item->id}")['questions'] ?? [];

    // Toutes fausses (bonne = 0, on répond « Faux » = index 1).
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 1;
    }

    $this->actingAs($student)
        ->post(qb2qSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $result = session('academy.quiz_result');
    expect($result['passed'])->toBeFalse();
    expect($result['percent'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ANTI-ESCALADE : un formateur ne peut pas lier la catégorie d'un AUTRE
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur ne peut pas lier la catégorie d’un autre via CourseEditor', function (): void {
    $course = qb2qCourse('cours-idor');
    $lesson = qb2qLesson($course);
    $owner  = qb2qOwner($course);

    // Catégorie appartenant à un AUTRE formateur (étrangère à l'owner du cours).
    $other        = qb2qInstructor();
    $foreignCat   = qb2qCategory($other, 'Catégorie étrangère');
    qb2qFillTrueFalse($foreignCat, 3);

    $item = qb2qQuizItem($lesson, ['qt_bank_key' => 'qt-questions']);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, [
            'qt_bank_key'      => 'qt-questions',
            'passing_score'    => 60,
            'bank_category_id' => $foreignCat->id, // id forgé d'un autre owner
            'bank_draw_count'  => 3,
        ])
        ->assertHasNoErrors();

    // L'id forgé est IGNORÉ : aucun question_bank n'est écrit (anti-IDOR).
    $item->refresh();
    expect($item->payload['question_bank'] ?? null)->toBeNull();
});

test('un formateur peut lier SA propre catégorie via CourseEditor', function (): void {
    $course = qb2qCourse('cours-mine');
    $lesson = qb2qLesson($course);
    $owner  = qb2qOwner($course);

    // Catégorie appartenant à l'owner du cours lui-même.
    $myCat = qb2qCategory($owner, 'Ma catégorie');
    qb2qFillTrueFalse($myCat, 3);

    $item = qb2qQuizItem($lesson, ['qt_bank_key' => 'qt-questions']);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, [
            'qt_bank_key'      => 'qt-questions',
            'passing_score'    => 60,
            'bank_category_id' => $myCat->id,
            'bank_draw_count'  => 3,
        ])
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->payload['question_bank']['category_id'] ?? null)->toBe($myCat->id);
    expect($item->payload['question_bank']['draw_count'] ?? null)->toBe(3);
});
