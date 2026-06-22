<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - V1-e : QCM À RÉPONSES MULTIPLES avec CRÉDIT PARTIEL borné.
 *
 * Prouve que :
 *  - CRÉDIT PARTIEL : fraction = max(0, (#bonnes cochées - #mauvaises cochées) / #bonnes) ;
 *    points obtenus = round(fraction * points). Jamais négatif.
 *  - BADGE « sans faute » : une question multi n'est comptée correcte que si fraction == 1.
 *  - MÉLANGE (V1-d) : le TABLEAU d'indices corrects suit la permutation → scoring exact.
 *  - BANQUE : mapToRoundItem (via drawFromCategory) produit `multiple` = true + `correct`
 *    = TABLEAU à partir de payload['multiple'] + payload['correct_set'].
 *  - RÉTROCOMPAT : QCM simple (correct int) strictement inchangé.
 *  - ÉDITEUR : validation (>= 1 bonne en multi) + bascule radio (simple) ↔ cases (multi).
 *
 * Autonome : helpers préfixés v1e. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Services\QuestionBankService;
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
// Helpers v1e (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Un item de round QCM MULTI : 4 choix par défaut, points 10 (fractions nettes). */
function v1eMultiItem(array $correctSet, int $points = 10, int $nbChoices = 4): array
{
    return [
        'type'     => 'qcm',
        'multiple' => true,
        'question' => 'Question à réponses multiples',
        'choices'  => array_map(fn (int $k): string => "Choix $k", range(0, $nbChoices - 1)),
        'correct'  => $correctSet,
        'points'   => $points,
    ];
}

/** Score un round mono-question avec la liste d'index cochés. */
function v1eScore(array $item, array $checked): array
{
    return QuizService::score([$item], ['0' => $checked]);
}

function v1eInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function v1eCategory(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque V1-e',
        'position'  => 0,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Crédit partiel borné - 4 choix, 2 bons (indices 0 et 1), 10 points
// ─────────────────────────────────────────────────────────────────────────────

test('les 2 bonnes exactement → fraction 1 → points pleins', function (): void {
    $r = v1eScore(v1eMultiItem([0, 1]), [0, 1]);

    expect($r['points_possible'])->toBe(10);
    expect($r['points_earned'])->toBe(10);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

test('1 bonne seule → fraction 0,5 → demi-points', function (): void {
    $r = v1eScore(v1eMultiItem([0, 1]), [0]);

    expect($r['points_earned'])->toBe(5);   // round(0.5 * 10)
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

test('1 bonne + 1 mauvaise → fraction 0 → zéro point', function (): void {
    $r = v1eScore(v1eMultiItem([0, 1]), [0, 2]); // gagne = 1 - 1 = 0

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);
});

test('2 bonnes + 1 mauvaise → fraction 0,5 → demi-points', function (): void {
    $r = v1eScore(v1eMultiItem([0, 1]), [0, 1, 2]); // gagne = 2 - 1 = 1 ; /2 = 0.5

    expect($r['points_earned'])->toBe(5);
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

test('rien coché → zéro', function (): void {
    $r = v1eScore(v1eMultiItem([0, 1]), []);

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);
});

test('crédit partiel jamais négatif (que des mauvaises)', function (): void {
    $r = v1eScore(v1eMultiItem([0, 1]), [2, 3]); // gagne = 0 - 2 = -2 → borné à 0

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['points_earned'])->toBeGreaterThanOrEqual(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Badge « sans faute » : correct seulement si fraction == 1
// ─────────────────────────────────────────────────────────────────────────────

test('badge sans-faute : multi comptée correcte seulement si fraction 1', function (): void {
    // fraction 1 → correct = total
    $exact = v1eScore(v1eMultiItem([0, 1]), [0, 1]);
    expect($exact['correct'])->toBe($exact['total']); // 1 / 1

    // fraction 0,5 → NON comptée correcte
    $partial = v1eScore(v1eMultiItem([0, 1]), [1]);
    expect($partial['correct'])->toBe(0);
    expect($partial['total'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Mélange (V1-d) d'un QCM multi → scoring exact après shuffle
// ─────────────────────────────────────────────────────────────────────────────

test('mélange multi : le tableau correct suit la permutation, scoring exact', function (): void {
    $orig = v1eMultiItem([0, 1]); // bonnes = « Choix 0 », « Choix 1 »

    $shuffled   = QuizService::shuffleRound([$orig], false, true)[0];
    $newCorrect = $shuffled['correct'];

    // Les labels aux NOUVEAUX index corrects sont exactement les bonnes d'origine.
    $newLabels = array_map(fn (int $k): string => $shuffled['choices'][$k], $newCorrect);
    sort($newLabels);
    expect($newLabels)->toBe(['Choix 0', 'Choix 1']);

    // Soumettre exactement le nouveau set → fraction 1.
    $r = QuizService::score([$shuffled], ['0' => $newCorrect]);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);

    // Une seule des deux → fraction 0,5.
    $r2 = QuizService::score([$shuffled], ['0' => [$newCorrect[0]]]);
    expect($r2['percent'])->toBe(50);
    expect($r2['correct'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Banque : mapToRoundItem (via drawFromCategory) produit un item multi
// ─────────────────────────────────────────────────────────────────────────────

test('banque : une question mcq multi devient un item correct=tableau', function (): void {
    $owner = v1eInstructor();
    $cat   = v1eCategory($owner);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'mcq',
        'prompt'      => 'Choisissez les bonnes',
        'payload'     => [
            'choices'     => ['A', 'B', 'C', 'D'],
            'multiple'    => true,
            'correct_set' => [0, 2],
        ],
        'difficulty'  => 'moyen',
        'points'      => 4,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 123);

    expect($round)->toHaveCount(1);
    expect($round[0]['type'])->toBe('qcm');
    expect($round[0]['multiple'])->toBeTrue();
    expect($round[0]['correct'])->toBe([0, 2]);
    expect($round[0]['points'])->toBe(4);

    // Et il se note avec crédit partiel (toutes bonnes → 100 %).
    $r = QuizService::score($round, ['0' => [0, 2]]);
    expect($r['percent'])->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Rétrocompat : QCM simple strictement inchangé
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : QCM simple (correct int) inchangé', function (): void {
    $item = [
        'type'     => 'qcm',
        'question' => 'Q simple',
        'choices'  => ['Bonne', 'Mauvaise'],
        'correct'  => 0, // int → simple
    ];

    $good = QuizService::score([$item], ['0' => 0]);
    expect($good['percent'])->toBe(100);
    expect($good['correct'])->toBe(1);

    $bad = QuizService::score([$item], ['0' => 1]);
    expect($bad['percent'])->toBe(0);
    expect($bad['correct'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Éditeur (Livewire) : validation + bascule radio ↔ cases
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : QCM multi enregistre payload multiple + correct_set', function (): void {
    $instructor = v1eInstructor();
    $cat        = v1eCategory($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'mcq')
        ->set('qPrompt', 'Cochez les bonnes réponses')
        ->set('qMultiple', true)
        ->set('qChoices', ['A', 'B', 'C', 'D'])
        ->set('qCorrectSet', [0, 2])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->payload['multiple'])->toBeTrue();
    expect($q->payload['correct_set'])->toBe([0, 2]);
});

test('éditeur : QCM multi sans bonne réponse → erreur de validation', function (): void {
    $instructor = v1eInstructor();
    $cat        = v1eCategory($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'mcq')
        ->set('qPrompt', 'Aucune bonne')
        ->set('qMultiple', true)
        ->set('qChoices', ['A', 'B', 'C'])
        ->set('qCorrectSet', [])
        ->call('saveQuestion')
        ->assertHasErrors('qCorrectSet');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : QCM simple (radio) reste un correct int, sans drapeau multiple', function (): void {
    $instructor = v1eInstructor();
    $cat        = v1eCategory($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'mcq')
        ->set('qPrompt', 'Une seule bonne')
        ->set('qMultiple', false)
        ->set('qChoices', ['A', 'B'])
        ->set('qCorrect', 1)
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->payload['correct'])->toBe(1);
    expect($q->payload['multiple'] ?? null)->toBeNull();
});

test('éditeur : édition recharge le sous-cas multi (hydratation)', function (): void {
    $instructor = v1eInstructor();
    $cat        = v1eCategory($instructor);

    $q = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $instructor->id,
        'type'        => 'mcq',
        'prompt'      => 'Multi à éditer',
        'payload'     => ['choices' => ['A', 'B', 'C'], 'multiple' => true, 'correct_set' => [1, 2]],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $q->id)
        ->assertSet('qMultiple', true)
        ->assertSet('qCorrectSet', [1, 2]);
});
