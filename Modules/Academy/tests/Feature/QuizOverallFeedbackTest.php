<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-a COUCHE 3 : feedback GLOBAL par tranche de score.
 *
 * Prouve que :
 *  - QuizFeedbackService sélectionne la BONNE borne (la plus haute <= percent) ;
 *  - la normalisation borne 0-100, dédoublonne, trie DESC, et ignore les lignes vides ;
 *  - CourseEditor enregistre des bornes valides dans payload['overall_feedback']
 *    (scopé manageStructure) et qu'une borne hors plage est ramenée dans [0,100].
 *
 * Autonome : helpers préfixés v1aof. SKIPPED si Academy off.
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
use Modules\Academy\Services\QuizFeedbackService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers v1aof
// ─────────────────────────────────────────────────────────────────────────────

function v1aofCourseWithQuiz(User $owner): array
{
    $course = Course::create([
        'slug'        => 'cours-of-'.uniqid(),
        'title'       => 'Cours OF',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);

    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'l-'.$chapter->id, 'position' => 1]);
    $item    = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'quiz',
        'title'     => 'Quiz',
        'position'  => 1,
        'payload'   => ['passing_score' => 60],
    ]);

    return [$course, $item];
}

function v1aofInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper de sélection : la bonne borne par percent
// ─────────────────────────────────────────────────────────────────────────────

test('QuizFeedbackService sélectionne la bonne borne par percent', function (): void {
    $boundaries = [
        ['min_percent' => 80, 'message' => 'A'],
        ['min_percent' => 50, 'message' => 'B'],
        ['min_percent' => 0,  'message' => 'C'],
    ];

    expect(QuizFeedbackService::messageForPercent($boundaries, 100))->toBe('A');
    expect(QuizFeedbackService::messageForPercent($boundaries, 80))->toBe('A');
    expect(QuizFeedbackService::messageForPercent($boundaries, 79))->toBe('B');
    expect(QuizFeedbackService::messageForPercent($boundaries, 60))->toBe('B');
    expect(QuizFeedbackService::messageForPercent($boundaries, 30))->toBe('C');
    expect(QuizFeedbackService::messageForPercent($boundaries, 0))->toBe('C');
});

test('messageForPercent retourne null sans borne applicable', function (): void {
    // Borne minimale = 50 : un score de 30 % n’atteint aucune borne → null.
    $boundaries = [['min_percent' => 50, 'message' => 'B']];
    expect(QuizFeedbackService::messageForPercent($boundaries, 30))->toBeNull();
    // Liste vide → null.
    expect(QuizFeedbackService::messageForPercent([], 100))->toBeNull();
});

test('normalizeBoundaries borne 0-100, trie DESC, dédoublonne et ignore le vide', function (): void {
    $raw = [
        ['min_percent' => 150, 'message' => 'trop haut → 100'],
        ['min_percent' => -5,  'message' => 'trop bas → 0'],
        ['min_percent' => 50,  'message' => 'milieu'],
        ['min_percent' => 50,  'message' => 'doublon (dernier gagne)'],
        ['min_percent' => 70,  'message' => ''],      // vide → ignoré
        ['min_percent' => '',  'message' => 'sans seuil'], // seuil vide → ignoré
    ];

    $clean = QuizFeedbackService::normalizeBoundaries($raw);

    // Tri DESC : 100, 50, 0.
    expect(array_column($clean, 'min_percent'))->toBe([100, 50, 0]);
    // Doublon 50 → dernier message.
    $byPercent = collect($clean)->keyBy('min_percent');
    expect($byPercent[50]['message'])->toBe('doublon (dernier gagne)');
    expect($byPercent[100]['message'])->toBe('trop haut → 100');
    expect($byPercent[0]['message'])->toBe('trop bas → 0');
});

// ─────────────────────────────────────────────────────────────────────────────
// CourseEditor : édition valide / hors-plage normalisée
// ─────────────────────────────────────────────────────────────────────────────

test('CourseEditor enregistre des bornes valides triées dans le payload', function (): void {
    $owner = v1aofInstructor();
    [$course, $item] = v1aofCourseWithQuiz($owner);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('loadOverallFeedback', $item->id)
        ->set("overallFeedback.{$item->id}", [
            ['min_percent' => 50, 'message' => 'Revois la matière'],
            ['min_percent' => 80, 'message' => 'Excellent'],
        ])
        ->call('saveOverallFeedback', $item->id)
        ->assertHasNoErrors();

    $payload = $item->fresh()->payload['overall_feedback'];
    // Trié DESC : 80 puis 50.
    expect(array_column($payload, 'min_percent'))->toBe([80, 50]);
    expect($payload[0]['message'])->toBe('Excellent');
});

test('CourseEditor ramène un seuil hors plage dans [0,100]', function (): void {
    $owner = v1aofInstructor();
    [$course, $item] = v1aofCourseWithQuiz($owner);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('loadOverallFeedback', $item->id)
        ->set("overallFeedback.{$item->id}", [
            ['min_percent' => 999, 'message' => 'plafonné'],
        ])
        ->call('saveOverallFeedback', $item->id)
        ->assertHasNoErrors();

    $payload = $item->fresh()->payload['overall_feedback'];
    expect($payload[0]['min_percent'])->toBe(100);
});

test('enregistrer une liste vide retire la clé (rétrocompat)', function (): void {
    $owner = v1aofInstructor();
    [$course, $item] = v1aofCourseWithQuiz($owner);

    // Pose une borne puis vide tout.
    $item->update(['payload' => array_merge($item->payload, [
        'overall_feedback' => [['min_percent' => 80, 'message' => 'A']],
    ])]);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("overallFeedback.{$item->id}", [['min_percent' => 80, 'message' => '']])
        ->call('saveOverallFeedback', $item->id)
        ->assertHasNoErrors();

    expect($item->fresh()->payload)->not->toHaveKey('overall_feedback');
});
