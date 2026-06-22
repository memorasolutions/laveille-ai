<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-c MÉTHODE DE NOTATION sur les tentatives (QuizGradeService).
 *
 * Prouve que :
 *  - highest / average / first / last appliquent la bonne agrégation des percent ;
 *  - 0 tentative → percent 0, attempts 0 ;
 *  - méthode absente/inconnue → défaut 'highest' ;
 *  - une méthode invalide est REJETÉE à l'édition (CourseEditor).
 *
 * Autonome : helpers préfixés v1cg. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\QuizGradeService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

function v1cgCourse(string $slug = 'cours-v1cg'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V1-c grading',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v1cgQuizItem(Course $course, array $payload = []): LessonItem
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

function v1cgOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

/** Crée 3 tentatives 40 %, 80 %, 60 % dans l'ordre chronologique pour user/item. */
function v1cgThreeAttempts(int $userId, LessonItem $item, Course $course): void
{
    $percents = [40, 80, 60];
    foreach ($percents as $i => $p) {
        QuizAttempt::create([
            'user_id'        => $userId,
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
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Les 4 méthodes sur 40 / 80 / 60
// ─────────────────────────────────────────────────────────────────────────────

test('highest = 80', function (): void {
    $course  = v1cgCourse('cours-highest');
    $student = User::factory()->create();
    $item    = v1cgQuizItem($course, ['grading_method' => 'highest']);
    v1cgThreeAttempts($student->id, $item, $course);

    $g = QuizGradeService::effectiveGrade($student->id, $item);
    expect($g['percent'])->toBe(80);
    expect($g['attempts'])->toBe(3);
    expect($g['method'])->toBe('highest');
});

test('average = 60', function (): void {
    $course  = v1cgCourse('cours-average');
    $student = User::factory()->create();
    $item    = v1cgQuizItem($course, ['grading_method' => 'average']);
    v1cgThreeAttempts($student->id, $item, $course);

    expect(QuizGradeService::effectiveGrade($student->id, $item)['percent'])->toBe(60);
});

test('first = 40', function (): void {
    $course  = v1cgCourse('cours-first');
    $student = User::factory()->create();
    $item    = v1cgQuizItem($course, ['grading_method' => 'first']);
    v1cgThreeAttempts($student->id, $item, $course);

    expect(QuizGradeService::effectiveGrade($student->id, $item)['percent'])->toBe(40);
});

test('last = 60', function (): void {
    $course  = v1cgCourse('cours-last');
    $student = User::factory()->create();
    $item    = v1cgQuizItem($course, ['grading_method' => 'last']);
    v1cgThreeAttempts($student->id, $item, $course);

    expect(QuizGradeService::effectiveGrade($student->id, $item)['percent'])->toBe(60);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. 0 tentative + défaut de méthode
// ─────────────────────────────────────────────────────────────────────────────

test('aucune tentative → percent 0, attempts 0', function (): void {
    $course  = v1cgCourse('cours-zero');
    $student = User::factory()->create();
    $item    = v1cgQuizItem($course, ['grading_method' => 'highest']);

    $g = QuizGradeService::effectiveGrade($student->id, $item);
    expect($g['percent'])->toBe(0);
    expect($g['attempts'])->toBe(0);
});

test('méthode absente ou inconnue → défaut highest', function (): void {
    $course  = v1cgCourse('cours-defaut');
    $student = User::factory()->create();

    $itemNone    = v1cgQuizItem($course, []); // pas de grading_method
    $itemUnknown = v1cgQuizItem($course, ['grading_method' => 'bidon']);

    v1cgThreeAttempts($student->id, $itemNone, $course);
    v1cgThreeAttempts($student->id, $itemUnknown, $course);

    expect(QuizGradeService::methodFor($itemNone))->toBe('highest');
    expect(QuizGradeService::methodFor($itemUnknown))->toBe('highest');
    // highest sur 40/80/60 = 80.
    expect(QuizGradeService::effectiveGrade($student->id, $itemNone)['percent'])->toBe(80);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Méthode invalide rejetée à l'édition (CourseEditor)
// ─────────────────────────────────────────────────────────────────────────────

test('une méthode de notation invalide est rejetée à l’édition', function (): void {
    $course = v1cgCourse('cours-edit');
    $owner  = v1cgOwner($course);
    $item   = v1cgQuizItem($course, ['grading_method' => 'highest']);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, ['grading_method' => 'bidon'])
        ->assertHasErrors('grading_method');

    // Inchangé en base (rien écrit de invalide).
    $item->refresh();
    expect($item->payload['grading_method'] ?? null)->toBe('highest');
});

test('une méthode valide est persistée à l’édition', function (): void {
    $course = v1cgCourse('cours-edit-ok');
    $owner  = v1cgOwner($course);
    $item   = v1cgQuizItem($course, ['grading_method' => 'highest']);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz', null, ['grading_method' => 'average'])
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->payload['grading_method'] ?? null)->toBe('average');
});
