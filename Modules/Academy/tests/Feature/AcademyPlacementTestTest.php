<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Test de positionnement adaptatif (CAT — Computer Adaptive Testing).
 *
 * Prouve que :
 *  (a) drapeau academy.placement_test_enabled OFF (défaut) → composant 404 ;
 *  (b) 2 bonnes réponses consécutives font monter la difficulté ;
 *  (c) 2 mauvaises réponses consécutives font descendre la difficulté ;
 *  (d) le niveau final recommande la bonne leçon (faible → début, fort → plus loin) ;
 *  (e) un apprenant NON INSCRIT au cours est refusé (403) ;
 *  (f) parcours complet bout en bout : toutes bonnes réponses converge vers 'fort'
 *      en MOINS de questions que le maximum, et crée une PlacementAttempt terminée.
 *
 * Autonome : helpers préfixés `pt`, aucune redéclaration d'une fonction d'un
 * autre fichier de test. Garde-fou : si le module Academy est désactivé, tous
 * les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\PlacementTest;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\PlacementAttempt;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Services\AdaptivePlacementService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    config()->set('academy.placement_test_enabled', false); // chaque test l'active lui-même si besoin
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers pt (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Cours publié gratuit avec $lessonCount leçons (1 seul chapitre), positions 1..N. */
function ptCourse(int $lessonCount = 8, string $slug = 'pt-cours'): Course
{
    $course = Course::create([
        'slug' => $slug, 'title' => 'Cours Positionnement', 'language' => 'fr-CA',
        'level' => 'intro', 'visibility' => 'public', 'access_type' => 'free',
        'status' => 'published', 'currency' => 'CAD',
    ]);

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);

    for ($i = 1; $i <= $lessonCount; $i++) {
        Lesson::create([
            'chapter_id' => $chapter->id, 'title' => "Leçon $i",
            'slug' => "pt-lecon-{$i}-{$course->id}", 'position' => $i,
        ]);
    }

    return $course->fresh(['chapters.lessons']);
}

function ptOwner(): User
{
    return User::factory()->create();
}

function ptStudent(): User
{
    return User::factory()->create();
}

function ptEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id' => $course->id, 'user_id' => $user->id, 'status' => 'active',
        'source' => 'admin', 'enrolled_at' => now(),
    ]);
}

/** Catégorie de banque de questions, owner-scopée. */
function ptCategory(User $owner, string $name = 'Banque PT'): QuestionCategory
{
    return QuestionCategory::create(['owner_id' => $owner->id, 'parent_id' => null, 'name' => $name, 'position' => 0]);
}

/** $count questions mcq à la $difficulty donnée, correct = index 0 systématiquement. */
function ptMcqBatch(QuestionCategory $cat, string $difficulty, int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        Question::create([
            'category_id' => $cat->id, 'owner_id' => $cat->owner_id, 'type' => 'mcq',
            'prompt' => "Question {$difficulty} #{$i}",
            'payload' => ['choices' => ['Bonne réponse', 'Mauvaise A', 'Mauvaise B'], 'correct' => 0],
            'difficulty' => $difficulty, 'is_active' => true,
        ]);
    }
}

/** Attache un item quiz (lié à la catégorie de banque) à la première leçon du cours. */
function ptQuizItem(Course $course, QuestionCategory $cat): LessonItem
{
    $lesson = $course->chapters->first()->lessons->first();

    return LessonItem::create([
        'lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Quiz', 'position' => 99,
        'payload' => ['question_bank' => ['category_id' => $cat->id, 'draw_count' => 1]],
        'is_required' => false,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF (défaut) → 404
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau off (défaut) : le composant de positionnement renvoie 404', function (): void {
    $course  = ptCourse();
    $student = ptStudent();
    ptEnroll($course, $student);
    $this->actingAs($student);

    Livewire::test(PlacementTest::class, ['course' => $course])->assertStatus(404);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) 2 bonnes réponses consécutives font monter la difficulté
// ─────────────────────────────────────────────────────────────────────────────

test('2 bonnes réponses consécutives font monter la difficulté', function (): void {
    $service = new AdaptivePlacementService();

    expect($service->nextDifficulty('moyen', 2, 0))->toBe('difficile');
    expect($service->nextDifficulty('facile', 2, 0))->toBe('moyen');
    expect($service->nextDifficulty('moyen', 1, 0))->toBe('moyen');
    expect($service->nextDifficulty('difficile', 2, 0))->toBe('difficile');
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) 2 mauvaises réponses consécutives font descendre la difficulté
// ─────────────────────────────────────────────────────────────────────────────

test('2 mauvaises réponses consécutives font descendre la difficulté', function (): void {
    $service = new AdaptivePlacementService();

    expect($service->nextDifficulty('moyen', 0, 2))->toBe('facile');
    expect($service->nextDifficulty('difficile', 0, 2))->toBe('moyen');
    expect($service->nextDifficulty('moyen', 0, 1))->toBe('moyen');
    expect($service->nextDifficulty('facile', 0, 2))->toBe('facile');
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Le niveau final recommande la bonne leçon (faible = début, fort = plus loin)
// ─────────────────────────────────────────────────────────────────────────────

test('le niveau final recommande la bonne leçon (faible = début, fort = plus loin)', function (): void {
    $course  = ptCourse(8);
    $service = new AdaptivePlacementService();

    expect($service->estimateLevel('facile'))->toBe('faible');
    expect($service->estimateLevel('moyen'))->toBe('moyen');
    expect($service->estimateLevel('difficile'))->toBe('fort');

    $lessons = $service->orderedLessons($course)->values();
    $weak    = $service->recommendStartingLesson($course, 'faible');
    $strong  = $service->recommendStartingLesson($course, 'fort');

    expect($weak->id)->toBe($lessons->first()->id);

    $expectedStrongIndex = (int) floor(8 * 0.5);
    expect($strong->id)->toBe($lessons->get($expectedStrongIndex)->id);

    expect($lessons->search(fn ($l) => $l->id === $strong->id))
        ->toBeGreaterThan($lessons->search(fn ($l) => $l->id === $weak->id));
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Un apprenant NON INSCRIT au cours est refusé (403)
// ─────────────────────────────────────────────────────────────────────────────

test('un apprenant NON inscrit au cours est refusé', function (): void {
    config()->set('academy.placement_test_enabled', true);

    $course   = ptCourse();
    $stranger = ptStudent(); // non inscrit
    $this->actingAs($stranger);

    Livewire::test(PlacementTest::class, ['course' => $course])->assertStatus(403);
});

// ─────────────────────────────────────────────────────────────────────────────
// (f) Parcours complet bout en bout : converge vers 'fort' en moins que le maximum
// ─────────────────────────────────────────────────────────────────────────────

test('parcours complet : toutes bonnes réponses converge vers fort en moins de questions que le maximum', function (): void {
    config()->set('academy.placement_test_enabled', true);

    $course = ptCourse(8);
    $owner  = ptOwner();
    $cat    = ptCategory($owner);
    ptMcqBatch($cat, 'moyen', 3);
    ptMcqBatch($cat, 'difficile', 3);
    ptQuizItem($course, $cat);

    $student = ptStudent();
    ptEnroll($course, $student);
    $this->actingAs($student);

    $livewire = Livewire::test(PlacementTest::class, ['course' => $course]);
    $livewire->call('startTest');
    expect($livewire->get('started'))->toBeTrue();

    $iterations = 0;
    while (! $livewire->get('finished') && $iterations < 10) {
        $livewire->call('submitAnswer', 0);
        expect($livewire->get('lastAnswerCorrect'))->toBeTrue();
        $livewire->call('advance');
        $iterations++;
    }

    expect($livewire->get('finished'))->toBeTrue();
    expect($livewire->get('estimatedLevel'))->toBe('fort');
    expect($iterations)->toBeLessThan(AdaptivePlacementService::MAX_QUESTIONS);

    $attempt = PlacementAttempt::where('user_id', $student->id)
        ->where('course_id', $course->id)
        ->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->completed_at)->not->toBeNull();
    expect($attempt->estimated_level)->toBe('fort');
    expect($attempt->recommended_lesson_id)->not->toBeNull();
});
