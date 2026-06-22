<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - ORDONNANCEMENT (« mettre dans le bon ordre », type Moodle « Ordering »).
 *
 * Prouve que :
 *  - BANQUE : une question `ordering` (payload['items'] = ordre correct) devient un item
 *    de round `type=ordonnancement` dont les `elements` sont MÉLANGÉS, tout en gardant la
 *    correspondance vers l'ordre correct via `answer` (position absolue 0-based par élément).
 *  - SCORING (crédit partiel par position absolue) : ordre exact → points pleins
 *    (fraction 1) ; 2/4 bien placés → 0,5 ; ordre inverse → fraction basse ; badge
 *    « sans faute » compté correct SEULEMENT si fraction == 1.
 *  - MÉLANGE (V1-d) : shuffleRound n'altère PAS le scoring d'un ordonnancement.
 *  - RÉTROCOMPAT : les autres types inchangés ; un round mixte (qcm + ordering) noté juste.
 *  - ÉDITEUR : validation (>= 2 éléments non vides) + enregistrement/hydratation du payload.
 *
 * Autonome : helpers préfixés f1 (aucune redéclaration). SKIPPED si Academy off.
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
// Helpers f1 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Un item de round ORDONNANCEMENT direct (sans passer par la banque) : `answer`
 * = position absolue correcte (0-based) de chaque élément affiché. Par défaut
 * l'identité [0,1,2,3] → « réponse exacte » = soumettre exactement `answer`.
 *
 * @param  array<int, int>  $answer
 * @return array<string, mixed>
 */
function f1OrderItem(array $answer = [0, 1, 2, 3], int $points = 8): array
{
    $n = count($answer);

    return [
        'type'     => 'ordonnancement',
        'question' => 'Mettez dans le bon ordre',
        'elements' => array_map(fn (int $k): string => "Élément $k", range(0, $n - 1)),
        'answer'   => $answer,
        'points'   => $points,
    ];
}

/** Score un round mono-question d'ordonnancement avec la liste de positions soumises. */
function f1Score(array $item, array $given): array
{
    return QuizService::score([$item], ['0' => $given]);
}

function f1Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function f1Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque ordonnancement',
        'position'  => 0,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Banque : tirage → item mélangé, ordre correct conservé en interne
// ─────────────────────────────────────────────────────────────────────────────

test('banque : une question ordering devient un item ordonnancement mélangé apparié', function (): void {
    $owner = f1Instructor();
    $cat   = f1Category($owner);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'ordering',
        'prompt'      => 'Ordre des étapes',
        'payload'     => ['items' => ['Un', 'Deux', 'Trois', 'Quatre']],
        'difficulty'  => 'moyen',
        'points'      => 8,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 7);

    expect($round)->toHaveCount(1);
    $item = $round[0];

    expect($item['type'])->toBe('ordonnancement');
    expect($item['points'])->toBe(8);
    expect($item['elements'])->toHaveCount(4);
    expect($item['answer'])->toHaveCount(4);

    // `answer` est une PERMUTATION de 0..3 (positions absolues correctes).
    $sorted = $item['answer'];
    sort($sorted);
    expect($sorted)->toBe([0, 1, 2, 3]);

    // Correspondance : l'élément affiché j est bien celui dont la position correcte
    // est answer[j] dans l'ordre d'origine (Un=0, Deux=1, Trois=2, Quatre=3).
    $original = ['Un', 'Deux', 'Trois', 'Quatre'];
    foreach ($item['elements'] as $j => $label) {
        expect($label)->toBe($original[$item['answer'][$j]]);
    }

    // Soumettre exactement `answer` (placer chaque élément à sa position correcte) → 100 %.
    $r = QuizService::score([$item], ['0' => $item['answer']]);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Scoring : crédit partiel par position absolue
// ─────────────────────────────────────────────────────────────────────────────

test('ordre exact → fraction 1 → points pleins', function (): void {
    $r = f1Score(f1OrderItem([0, 1, 2, 3]), [0, 1, 2, 3]);

    expect($r['points_possible'])->toBe(8);
    expect($r['points_earned'])->toBe(8);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

test('2 éléments sur 4 bien placés → fraction 0,5 → demi-points', function (): void {
    // answer [0,1,2,3] ; donné [0,1,3,2] → positions 0 et 1 correctes (2/4).
    $r = f1Score(f1OrderItem([0, 1, 2, 3]), [0, 1, 3, 2]);

    expect($r['points_earned'])->toBe(4);  // round(0.5 * 8)
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);         // pas « sans faute »
});

test('ordre inverse → fraction basse (aucune position absolue correcte)', function (): void {
    $r = f1Score(f1OrderItem([0, 1, 2, 3]), [3, 2, 1, 0]);

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);
});

test('badge sans-faute : ordonnancement correct seulement si fraction 1', function (): void {
    $exact = f1Score(f1OrderItem([0, 1, 2, 3]), [0, 1, 2, 3]);
    expect($exact['correct'])->toBe($exact['total']); // 1 / 1

    $partial = f1Score(f1OrderItem([0, 1, 2, 3]), [0, 1, 2, 0]); // 3/4 placés
    expect($partial['correct'])->toBe(0);
    expect($partial['total'])->toBe(1);
});

test('réponse manquante / incomplète → fraction proportionnelle, jamais d’erreur', function (): void {
    // Donné incomplet (2 positions) : seules les positions présentes peuvent compter.
    $r = f1Score(f1OrderItem([0, 1, 2, 3]), [0, 1]);

    expect($r['points_earned'])->toBe(4); // 2/4 bien placés → 0,5 * 8
    expect($r['percent'])->toBe(50);

    // Rien soumis → zéro, sans exception.
    $empty = f1Score(f1OrderItem([0, 1, 2, 3]), []);
    expect($empty['points_earned'])->toBe(0);
    expect($empty['percent'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Mélange (V1-d) n'altère pas le score d'un ordonnancement
// ─────────────────────────────────────────────────────────────────────────────

test('mélange V1-d : l’ordonnancement est laissé tel quel, scoring inchangé', function (): void {
    $orig = f1OrderItem([2, 0, 3, 1]); // answer arbitraire (permutation)

    $shuffled = QuizService::shuffleRound([$orig], false, true)[0];

    // shuffleQuestionChoices ne touche pas l'ordonnancement : item identique.
    expect($shuffled['answer'])->toBe($orig['answer']);
    expect($shuffled['elements'])->toBe($orig['elements']);

    // Score exact identique avant/après mélange.
    $r = QuizService::score([$shuffled], ['0' => [2, 0, 3, 1]]);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Rétrocompat : autres types inchangés + round mixte (qcm + ordering)
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : un QCM simple reste noté comme avant', function (): void {
    $item = [
        'type'     => 'qcm',
        'question' => 'Q simple',
        'choices'  => ['Bonne', 'Mauvaise'],
        'correct'  => 0,
    ];

    $good = QuizService::score([$item], ['0' => 0]);
    expect($good['percent'])->toBe(100);
    expect($good['correct'])->toBe(1);
});

test('round mixte qcm + ordonnancement noté correctement', function (): void {
    $qcm = [
        'type'     => 'qcm',
        'question' => 'Capitale ?',
        'choices'  => ['Québec', 'Montréal'],
        'correct'  => 0,
        'points'   => 2,
    ];
    $order = f1OrderItem([0, 1, 2, 3], 8);

    // Tout correct (qcm = 0, ordre = answer).
    $all = QuizService::score([$qcm, $order], ['0' => 0, '1' => [0, 1, 2, 3]]);
    expect($all['points_possible'])->toBe(10);
    expect($all['points_earned'])->toBe(10);
    expect($all['percent'])->toBe(100);
    expect($all['correct'])->toBe(2);

    // QCM bon, ordre à moitié (2/4) : 2 + round(0.5*8) = 6 / 10 = 60 %.
    $mix = QuizService::score([$qcm, $order], ['0' => 0, '1' => [0, 1, 3, 2]]);
    expect($mix['points_earned'])->toBe(6);
    expect($mix['percent'])->toBe(60);
    expect($mix['correct'])->toBe(1); // seul le qcm est « sans faute »
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Éditeur (Livewire) : enregistrement, validation, hydratation
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : un ordonnancement enregistre payload items dans l’ordre', function (): void {
    $instructor = f1Instructor();
    $cat        = f1Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ordering')
        ->set('qPrompt', 'Ordonnez les étapes')
        ->set('qOrderingItems', ['Première', 'Deuxième', 'Troisième'])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->type)->toBe('ordering');
    expect($q->payload['items'])->toBe(['Première', 'Deuxième', 'Troisième']);
});

test('éditeur : moins de 2 éléments non vides → erreur de validation', function (): void {
    $instructor = f1Instructor();
    $cat        = f1Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ordering')
        ->set('qPrompt', 'Ordre incomplet')
        ->set('qOrderingItems', ['Seul', '', ''])
        ->call('saveQuestion')
        ->assertHasErrors('qOrderingItems');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : réordonnancement (moveOrderingItem) puis enregistrement', function (): void {
    $instructor = f1Instructor();
    $cat        = f1Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ordering')
        ->set('qPrompt', 'Ordre à corriger')
        ->set('qOrderingItems', ['B', 'A', 'C'])
        ->call('moveOrderingItem', 1, 'up')   // remonte « A » en tête → [A, B, C]
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->payload['items'])->toBe(['A', 'B', 'C']);
});

test('éditeur : édition recharge les éléments (hydratation)', function (): void {
    $instructor = f1Instructor();
    $cat        = f1Category($instructor);

    $q = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $instructor->id,
        'type'        => 'ordering',
        'prompt'      => 'À éditer',
        'payload'     => ['items' => ['X', 'Y', 'Z']],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $q->id)
        ->assertSet('qType', 'ordering')
        ->assertSet('qOrderingItems', ['X', 'Y', 'Z']);
});
