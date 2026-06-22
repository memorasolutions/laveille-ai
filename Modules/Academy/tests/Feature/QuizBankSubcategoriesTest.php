<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — QB3 : PARITÉ MOODLE pour l'héritage des sous-catégories.
 *
 * Prouve que :
 *  - un item quiz lié à une catégorie parente SANS la clé include_subcategories
 *    (item QB2 déjà existant) tire AUSSI les questions des sous-catégories
 *    (défaut = true → rétrocompat) ;
 *  - include_subcategories=false → tire UNIQUEMENT la catégorie parente directe ;
 *  - le compteur deep = questions ACTIVES parent + descendants (ignore inactives) ;
 *    une catégorie feuille = compte direct ;
 *  - le toggle de CourseEditor écrit bien le bool dans payload['question_bank'] ;
 *    un item sans catégorie liée n'écrit PAS de question_bank.
 *
 * Autonome : helpers préfixés qb3 (aucune redéclaration). SKIPPED si Academy off.
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
// Helpers qb3 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function qb3Course(string $slug = 'cours-qb3'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours QB3',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function qb3Lesson(Course $course): Lesson
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

function qb3Owner(Course $course): User
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

function qb3Category(User $owner, string $name = 'Parent', ?QuestionCategory $parent = null): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => $parent?->id,
        'name'      => $name,
        'position'  => 0,
    ]);
}

/** Crée N questions truefalse (réponse = Vrai) actives dans une catégorie. */
function qb3FillTrueFalse(QuestionCategory $cat, int $n, string $tag = ''): void
{
    for ($i = 0; $i < $n; $i++) {
        Question::create([
            'category_id' => $cat->id,
            'owner_id'    => $cat->owner_id,
            'type'        => 'truefalse',
            'prompt'      => trim("Affirmation $tag #$i (vraie)"),
            'payload'     => ['answer' => true],
            'difficulty'  => 'facile',
            'is_active'   => true,
        ]);
    }
}

/** Crée une question INACTIVE (ne doit jamais être comptée ni tirée). */
function qb3FillInactive(QuestionCategory $cat, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        Question::create([
            'category_id' => $cat->id,
            'owner_id'    => $cat->owner_id,
            'type'        => 'truefalse',
            'prompt'      => "Inactive #$i",
            'payload'     => ['answer' => true],
            'difficulty'  => 'facile',
            'is_active'   => false,
        ]);
    }
}

function qb3QuizItem(Lesson $lesson, array $payload): LessonItem
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

function qb3Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function qb3Student(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function qb3StartUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
}

/** Récupère le round joué en session (les questions restent côté serveur). */
function qb3Round(LessonItem $item): array
{
    return session("academy.quiz.{$item->id}")['questions'] ?? [];
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Item QB2 SANS clé include_subcategories → défaut true → tire la descendance
// ─────────────────────────────────────────────────────────────────────────────

test('un item QB2 (sans la clé) tire AUSSI les sous-catégories par défaut', function (): void {
    $course = qb3Course('cours-defaut');
    $lesson = qb3Lesson($course);
    $owner  = qb3Owner($course);

    $parent = qb3Category($owner, 'Parent');
    $child  = qb3Category($owner, 'Enfant', $parent);

    // 3 questions dans le parent direct, 5 dans la sous-catégorie.
    qb3FillTrueFalse($parent, 3, 'parent');
    qb3FillTrueFalse($child, 5, 'enfant');

    // Item lié au PARENT, façon QB2 : AUCUNE clé include_subcategories.
    $item = qb3QuizItem($lesson, ['question_bank' => ['category_id' => $parent->id, 'draw_count' => 8]]);

    $student = qb3Student();
    qb3Enroll($course, $student);

    $this->actingAs($student)
        ->post(qb3StartUrl($course, $lesson, $item))
        ->assertRedirect();

    $round = qb3Round($item);

    // 3 (parent) + 5 (enfant) = 8 questions tirées → la descendance est incluse.
    expect($round)->toHaveCount(8);
    foreach ($round as $q) {
        expect($q['theme'] ?? null)->toBe('banque');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. include_subcategories=false → tire UNIQUEMENT le parent direct
// ─────────────────────────────────────────────────────────────────────────────

test('include_subcategories=false limite le tirage à la catégorie parente', function (): void {
    $course = qb3Course('cours-false');
    $lesson = qb3Lesson($course);
    $owner  = qb3Owner($course);

    $parent = qb3Category($owner, 'Parent');
    $child  = qb3Category($owner, 'Enfant', $parent);

    qb3FillTrueFalse($parent, 3, 'parent');
    qb3FillTrueFalse($child, 5, 'enfant');

    // draw_count élevé : on veut TOUT ce qui est disponible dans le parent SEUL.
    $item = qb3QuizItem($lesson, [
        'question_bank' => [
            'category_id'           => $parent->id,
            'draw_count'            => 50,
            'include_subcategories' => false,
        ],
    ]);

    $student = qb3Student();
    qb3Enroll($course, $student);

    $this->actingAs($student)
        ->post(qb3StartUrl($course, $lesson, $item))
        ->assertRedirect();

    // Seules les 3 du parent direct (les 5 de l'enfant sont exclues).
    expect(qb3Round($item))->toHaveCount(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Compteur deep = actives parent + descendants ; feuille = compte direct
// ─────────────────────────────────────────────────────────────────────────────

test('activeQuestionCountDeep compte les actives du parent et de ses descendants', function (): void {
    $course = qb3Course('cours-count');
    $owner  = qb3Owner($course);

    $parent = qb3Category($owner, 'Parent');
    $child  = qb3Category($owner, 'Enfant', $parent);

    qb3FillTrueFalse($parent, 2, 'parent');   // 2 actives
    qb3FillTrueFalse($child, 4, 'enfant');    // 4 actives
    qb3FillInactive($parent, 3);              // 3 INACTIVES (ignorées)
    qb3FillInactive($child, 2);               // 2 INACTIVES (ignorées)

    // Parent (avec enfant) = 2 + 4 = 6 actives (descendance incluse, inactives ignorées).
    expect($parent->fresh()->activeQuestionCountDeep())->toBe(6);

    // Feuille (l'enfant n'a pas de descendant) = compte direct des actives = 4.
    expect($child->fresh()->activeQuestionCountDeep())->toBe(4);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Le toggle écrit bien le bool ; un item sans catégorie n'écrit pas question_bank
// ─────────────────────────────────────────────────────────────────────────────

test('le toggle écrit le bool include_subcategories dans le payload', function (): void {
    $course = qb3Course('cours-toggle');
    $lesson = qb3Lesson($course);
    $owner  = qb3Owner($course);

    $cat = qb3Category($owner, 'Ma catégorie');
    qb3FillTrueFalse($cat, 3, 'mine');

    $item = qb3QuizItem($lesson, ['qt_bank_key' => 'qt-questions']);

    // Décoché → false.
    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, [
            'qt_bank_key'                => 'qt-questions',
            'passing_score'              => 60,
            'bank_category_id'           => $cat->id,
            'bank_draw_count'            => 3,
            'bank_include_subcategories' => false,
        ])
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->payload['question_bank']['include_subcategories'] ?? null)->toBeFalse();

    // Coché → true.
    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, [
            'qt_bank_key'                => 'qt-questions',
            'passing_score'              => 60,
            'bank_category_id'           => $cat->id,
            'bank_draw_count'            => 3,
            'bank_include_subcategories' => true,
        ])
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->payload['question_bank']['include_subcategories'] ?? null)->toBeTrue();
});

test('un item sans catégorie liée n’écrit pas de question_bank', function (): void {
    $course = qb3Course('cours-nocat');
    $lesson = qb3Lesson($course);
    $owner  = qb3Owner($course);

    $item = qb3QuizItem($lesson, ['qt_bank_key' => 'qt-questions']);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, [
            'qt_bank_key'                => 'qt-questions',
            'passing_score'              => 60,
            'bank_category_id'           => null,
            'bank_include_subcategories' => true,
        ])
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->payload['question_bank'] ?? null)->toBeNull();
});
