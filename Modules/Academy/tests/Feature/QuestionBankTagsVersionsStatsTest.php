<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F17 : TAGS + VERSIONS + STATISTIQUES de la banque de questions.
 *
 * Prouve que :
 *  - TAGS : un formateur attache une etiquette a sa question (creee a la volee,
 *    owner-scopee) et filtre la liste par etiquette ;
 *  - ANTI-IDOR : une etiquette homonyme d'un AUTRE owner n'est jamais reutilisee
 *    (un tag DISTINCT est cree pour l'owner courant) ; un filtre sur le tag d'autrui
 *    ne fuit aucune question ;
 *  - STATISTIQUES : l'indice de facilite (% de bonnes reponses) est calcule
 *    correctement depuis academy_quiz_attempts via la cle stable bank_question_id ;
 *  - VERSIONS : une edition du CONTENU archive d'abord l'etat precedent ;
 *  - RETROCOMPAT : une question sans tag/version se comporte comme avant (zero stat).
 *
 * Autonome : helpers prefixes f17 (aucune redeclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuestionTag;
use Modules\Academy\Models\QuestionVersion;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\QuestionStatsService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers f17 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function f17Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function f17Category(User $owner, string $name = 'Catégorie'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => $name,
        'position'  => 0,
    ]);
}

function f17Question(User $owner, QuestionCategory $cat, string $prompt = 'Énoncé ?'): Question
{
    return Question::create([
        'owner_id'    => $owner->id,
        'category_id' => $cat->id,
        'type'        => 'mcq',
        'prompt'      => $prompt,
        'payload'     => ['choices' => ['A', 'B'], 'correct' => 0],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);
}

/** Cours + item quiz minimal (satisfait les clés étrangères de academy_quiz_attempts). */
function f17QuizItem(): LessonItem
{
    $course = Course::create([
        'slug'        => 'cours-f17-'.uniqid(),
        'title'       => 'Cours F17',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);

    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);
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
        'payload'     => [],
        'is_required' => false,
    ]);
}

/** Tentative avec un snapshot d'UNE question de banque (qcm) + réponse donnée. */
function f17Attempt(int $bankQuestionId, int $given, int $correctIndex = 0): QuizAttempt
{
    $item = f17QuizItem();

    return QuizAttempt::create([
        'user_id'            => User::factory()->create()->id,
        'lesson_item_id'     => $item->id,
        'course_id'          => $item->lesson->chapter->course_id,
        'score'              => $given === $correctIndex ? 1 : 0,
        'max_score'          => 1,
        'percent'            => $given === $correctIndex ? 100 : 0,
        'passed'             => $given === $correctIndex,
        'answers'            => ['0' => $given],
        'questions_snapshot' => [[
            'type'             => 'qcm',
            'question'         => 'Q',
            'choices'          => ['A', 'B'],
            'correct'          => $correctIndex,
            'points'           => 1,
            'bank_question_id' => $bankQuestionId,
        ]],
        'submitted_at'       => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// TAGS
// ─────────────────────────────────────────────────────────────────────────────

test('un formateur attache une étiquette créée à la volée à sa question', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat);

    Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->call('editQuestion', $q->id)
        ->set('qTags', 'Grammaire, Niveau 2')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q->refresh();
    expect($q->tags)->toHaveCount(2);
    expect($q->tags->pluck('name')->all())->toContain('Grammaire', 'Niveau 2');
    // Owner-scope : les tags appartiennent au formateur.
    expect($q->tags->every(fn ($t) => $t->owner_id === $owner->id))->toBeTrue();
});

test('le filtre par étiquette ne montre que les questions taguées', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $qA    = f17Question($owner, $cat, 'Question A');
    $qB    = f17Question($owner, $cat, 'Question B');

    $tag = QuestionTag::create(['owner_id' => $owner->id, 'name' => 'Algèbre', 'slug' => 'algebre']);
    $qA->tags()->attach($tag->id);

    $component = Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('filterTagId', $tag->id);

    $ids = $component->instance()->questions->pluck('id')->all();
    expect($ids)->toContain($qA->id)->not->toContain($qB->id);
});

test('anti-IDOR : une étiquette homonyme d’un autre owner crée un tag distinct', function (): void {
    $other = f17Instructor();
    $otherTag = QuestionTag::create(['owner_id' => $other->id, 'name' => 'Partagé', 'slug' => 'partage']);

    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat);

    Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->call('editQuestion', $q->id)
        ->set('qTags', 'Partagé')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q->refresh();
    // Le tag attaché appartient à l'owner courant, JAMAIS celui de l'autre formateur.
    expect($q->tags)->toHaveCount(1);
    expect($q->tags->first()->owner_id)->toBe($owner->id);
    expect($q->tags->first()->id)->not->toBe($otherTag->id);
    // Deux tags « partage » distincts coexistent (un par owner).
    expect(QuestionTag::where('slug', 'partage')->count())->toBe(2);
});

test('un filtre sur le tag d’un autre owner ne fuit aucune question', function (): void {
    $other    = f17Instructor();
    $otherTag = QuestionTag::create(['owner_id' => $other->id, 'name' => 'Autre', 'slug' => 'autre']);

    $owner = f17Instructor();
    $cat   = f17Category($owner);
    f17Question($owner, $cat, 'Question A');

    $component = Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('filterTagId', $otherTag->id);

    expect($component->instance()->questions)->toHaveCount(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// STATISTIQUES
// ─────────────────────────────────────────────────────────────────────────────

test('l’indice de facilité = pourcentage de bonnes réponses sur les tentatives', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat);

    // 4 tentatives : 3 bonnes (given=0), 1 mauvaise (given=1) → facilité 75 %.
    f17Attempt($q->id, 0);
    f17Attempt($q->id, 0);
    f17Attempt($q->id, 0);
    f17Attempt($q->id, 1);

    $stats = QuestionStatsService::forQuestions([$q->id]);

    expect($stats[$q->id]['uses'])->toBe(4);
    expect($stats[$q->id]['correct'])->toBe(3);
    expect($stats[$q->id]['facility'])->toBe(75);
    expect($stats[$q->id]['has_data'])->toBeTrue();
});

test('une question jamais jouée renvoie zéro statistique', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat);

    $stats = QuestionStatsService::forQuestions([$q->id]);

    expect($stats[$q->id]['uses'])->toBe(0);
    expect($stats[$q->id]['has_data'])->toBeFalse();
    expect($stats[$q->id]['facility'])->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// VERSIONS
// ─────────────────────────────────────────────────────────────────────────────

test('éditer le contenu d’une question archive l’état précédent', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat, 'Énoncé initial ?');

    expect($q->versions()->count())->toBe(0);

    Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->call('editQuestion', $q->id)
        ->set('qPrompt', 'Énoncé modifié ?')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q->refresh();
    expect($q->prompt)->toBe('Énoncé modifié ?');

    $versions = $q->versions()->get();
    expect($versions)->toHaveCount(1);
    // La version archivée contient l'ANCIEN énoncé.
    expect($versions->first()->prompt)->toBe('Énoncé initial ?');
    expect($versions->first()->version)->toBe(1);
    expect($versions->first()->owner_id)->toBe($owner->id);
});

test('une édition purement cosmétique (points) ne crée pas de version', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat);

    Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->call('editQuestion', $q->id)
        ->set('qPoints', 5)
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q->refresh();
    expect($q->points)->toBe(5);
    expect($q->versions()->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('une question sans tag ni version reste pleinement fonctionnelle', function (): void {
    $owner = f17Instructor();
    $cat   = f17Category($owner);
    $q     = f17Question($owner, $cat);

    expect($q->tags)->toHaveCount(0);
    expect($q->versions()->count())->toBe(0);

    // Le gestionnaire affiche la question sans erreur (stats à zéro).
    $component = Livewire::actingAs($owner)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->assertOk();

    expect($component->instance()->questions)->toHaveCount(1);
});
