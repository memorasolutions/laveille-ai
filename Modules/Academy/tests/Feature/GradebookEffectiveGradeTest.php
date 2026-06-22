<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-c CARNET DE NOTES branché sur la note effective + points de banque.
 *
 * Prouve que :
 *  - la colonne quiz du gradebook reflète la MÉTHODE de notation de l'item
 *    (QuizGradeService) et non une somme brute ;
 *  - le champ « points » d'une question de banque est validé (1..100) à l'édition ;
 *  - la migration additive (colonne points) existe.
 *
 * Autonome : helpers préfixés v1cgb. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Chapter;
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

function v1cgbCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V1-c gradebook',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v1cgbOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

function v1cgbStudent(Course $course): User
{
    $student = User::factory()->create();
    $student->assignRole('student');
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $student->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $student;
}

function v1cgbQuizItem(Course $course, array $payload): LessonItem
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => $payload,
        'is_required' => false,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. La colonne quiz reflète la méthode de notation choisie
// ─────────────────────────────────────────────────────────────────────────────

test('le gradebook affiche la note effective selon la méthode (highest)', function (): void {
    $course  = v1cgbCourse('cours-gb-highest');
    $owner   = v1cgbOwner($course);
    $student = v1cgbStudent($course);

    $item = v1cgbQuizItem($course, ['grading_method' => 'highest', 'passing_score' => 60]);

    // Tentatives 40, 80, 60 → highest = 80.
    foreach ([40, 80, 60] as $i => $p) {
        QuizAttempt::create([
            'user_id'        => $student->id,
            'lesson_item_id' => $item->id,
            'course_id'      => $course->id,
            'score'          => $p,
            'max_score'      => 100,
            'percent'        => $p,
            'passed'         => $p >= 60,
            'answers'        => [],
            'submitted_at'   => now()->addMinutes($i),
        ]);
    }

    $component = Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $course])
        ->call('toggleGradebook');

    $gb = $component->get('gradebook');
    expect($gb['quizTotal'])->toBe(100);

    $row = collect($gb['students'])->firstWhere('user.id', $student->id);
    expect($row)->not->toBeNull();
    expect($row['quizScore'])->toBe(80); // highest
});

test('le gradebook suit la méthode average (60) plutôt qu’une somme brute', function (): void {
    $course  = v1cgbCourse('cours-gb-average');
    $owner   = v1cgbOwner($course);
    $student = v1cgbStudent($course);

    $item = v1cgbQuizItem($course, ['grading_method' => 'average', 'passing_score' => 60]);

    foreach ([40, 80, 60] as $i => $p) {
        QuizAttempt::create([
            'user_id'        => $student->id,
            'lesson_item_id' => $item->id,
            'course_id'      => $course->id,
            'score'          => $p,
            'max_score'      => 100,
            'percent'        => $p,
            'passed'         => $p >= 60,
            'answers'        => [],
            'submitted_at'   => now()->addMinutes($i),
        ]);
    }

    $gb = Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $course])
        ->call('toggleGradebook')
        ->get('gradebook');

    $row = collect($gb['students'])->firstWhere('user.id', $student->id);
    expect($row['quizScore'])->toBe(60); // average de 40/80/60
});

test('le gradebook affiche 0 pour un étudiant sans tentative', function (): void {
    $course  = v1cgbCourse('cours-gb-zero');
    $owner   = v1cgbOwner($course);
    $student = v1cgbStudent($course);

    v1cgbQuizItem($course, ['grading_method' => 'highest']);

    $gb = Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $course])
        ->call('toggleGradebook')
        ->get('gradebook');

    $row = collect($gb['students'])->firstWhere('user.id', $student->id);
    expect($row['quizScore'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Points de banque : validation (1..100) à l'édition
// ─────────────────────────────────────────────────────────────────────────────

test('le champ points est validé entre 1 et 100 à l’édition d’une question', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('instructor');

    $category = QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Cat V1-c',
        'position'  => 0,
    ]);

    Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $category->id)
        ->set('qType', 'truefalse')
        ->set('qPrompt', 'Affirmation vraie ?')
        ->set('qAnswerTrue', true)
        ->set('qPoints', 200) // hors borne
        ->call('saveQuestion')
        ->assertHasErrors('qPoints');

    expect(Question::where('category_id', $category->id)->count())->toBe(0);
});

test('une question avec des points valides est persistée', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('instructor');

    $category = QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Cat V1-c ok',
        'position'  => 0,
    ]);

    Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $category->id)
        ->set('qType', 'truefalse')
        ->set('qPrompt', 'Affirmation vraie ?')
        ->set('qAnswerTrue', true)
        ->set('qPoints', 5)
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $category->id)->first();
    expect($q)->not->toBeNull();
    expect($q->points)->toBe(5);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Migration additive
// ─────────────────────────────────────────────────────────────────────────────

test('la colonne points existe sur academy_questions (migration additive)', function (): void {
    expect(Schema::hasColumn('academy_questions', 'points'))->toBeTrue();
});
