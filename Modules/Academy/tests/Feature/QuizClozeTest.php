<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - CLOZE / TEXTE À TROUS (sous-questions intégrées, type Moodle
 * « Embedded answers »).
 *
 * Prouve que :
 *  - BANQUE : une question `cloze` (payload text à marqueurs [[n]] + blanks) devient un
 *    item de round `type=cloze` dont les `segments` d'affichage N'EXPOSENT PAS les bonnes
 *    réponses (ni accepted ni correct) ; les corrigés vivent dans `blanks` (serveur).
 *  - SCORING (crédit partiel par trou) : tous trous corrects → points pleins (fraction 1
 *    + badge sans-faute) ; 1/2 → 0,5 ; aucun → 0. short insensible casse/espaces ; mcq
 *    par index.
 *  - RÉTROCOMPAT : les 5 types existants inchangés ; un round mixte (qcm + cloze) noté juste.
 *  - ÉDITEUR : validation (>= 1 trou ; short a >= 1 réponse acceptée ; mcq a >= 2 choix +
 *    1 correct ; un marqueur [[n]] doit résoudre un trou) + enregistrement + hydratation.
 *
 * Autonome : helpers préfixés f2 (aucune redéclaration). SKIPPED si Academy off.
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
// Helpers f2 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Un item de round CLOZE direct (sans passer par la banque) : 1 trou short (index 0,
 * accepté « Québec »/« Quebec ») + 1 trou mcq (index 1, choix A/B/C, correct = 1).
 *
 * @return array<string, mixed>
 */
function f2ClozeItem(int $points = 4): array
{
    return [
        'type'     => 'cloze',
        'question' => 'Complétez la phrase',
        'segments' => [
            ['type' => 'text',  'value' => 'La capitale est '],
            ['type' => 'blank', 'index' => 0, 'kind' => 'short'],
            ['type' => 'text',  'value' => ' et la lettre est '],
            ['type' => 'blank', 'index' => 1, 'kind' => 'mcq', 'choices' => ['A', 'B', 'C']],
            ['type' => 'text',  'value' => '.'],
        ],
        'blanks' => [
            0 => ['kind' => 'short', 'accepted' => ['Québec', 'Quebec']],
            1 => ['kind' => 'mcq', 'choices' => ['A', 'B', 'C'], 'correct' => 1],
        ],
        'points' => $points,
    ];
}

/** Score un round mono-question de cloze avec le tableau de réponses par trou. */
function f2Score(array $item, array $given): array
{
    return QuizService::score([$item], ['0' => $given]);
}

function f2Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function f2Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque cloze',
        'position'  => 0,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Banque : tirage → segments d'affichage, corrigés non exposés
// ─────────────────────────────────────────────────────────────────────────────

test('banque : une question cloze devient un item cloze (segments sans corrigés)', function (): void {
    $owner = f2Instructor();
    $cat   = f2Category($owner);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'cloze',
        'prompt'      => 'Complétez',
        'payload'     => [
            'text'   => 'La capitale du Québec est [[1]] et la première lettre est [[2]].',
            'blanks' => [
                ['kind' => 'short', 'accepted' => ['Québec', 'Quebec']],
                ['kind' => 'mcq', 'choices' => ['A', 'B', 'C'], 'correct' => 1],
            ],
        ],
        'difficulty'  => 'moyen',
        'points'      => 6,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 7);

    expect($round)->toHaveCount(1);
    $item = $round[0];

    expect($item['type'])->toBe('cloze');
    expect($item['points'])->toBe(6);
    expect($item['segments'])->toBeArray();
    expect($item['blanks'])->toBeArray()->toHaveCount(2);

    // Les SEGMENTS d'affichage ne doivent JAMAIS exposer accepted/correct.
    $blankSegments = array_values(array_filter(
        $item['segments'],
        fn ($s): bool => ($s['type'] ?? '') === 'blank'
    ));
    expect($blankSegments)->toHaveCount(2);
    foreach ($blankSegments as $seg) {
        expect($seg)->not->toHaveKey('accepted');
        expect($seg)->not->toHaveKey('correct');
    }

    // Le corrigé vit dans `blanks` (serveur).
    expect($item['blanks'][0]['accepted'])->toBe(['Québec', 'Quebec']);
    expect($item['blanks'][1]['correct'])->toBe(1);

    // Soumettre les bonnes réponses (par trou) → 100 %.
    $r = QuizService::score([$item], ['0' => [0 => 'Québec', 1 => 1]]);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Scoring : crédit partiel par trou
// ─────────────────────────────────────────────────────────────────────────────

test('tous les trous corrects → fraction 1 → points pleins + badge sans-faute', function (): void {
    $r = f2Score(f2ClozeItem(4), [0 => 'Québec', 1 => 1]);

    expect($r['points_possible'])->toBe(4);
    expect($r['points_earned'])->toBe(4);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe($r['total']); // 1 / 1
});

test('1 trou sur 2 correct → fraction 0,5 → demi-points', function (): void {
    // short bon, mcq faux (index 0 au lieu de 1).
    $r = f2Score(f2ClozeItem(4), [0 => 'Québec', 1 => 0]);

    expect($r['points_earned'])->toBe(2); // round(0.5 * 4)
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);        // pas « sans faute »
});

test('aucun trou correct → 0', function (): void {
    $r = f2Score(f2ClozeItem(4), [0 => 'Paris', 1 => 2]);

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);
});

test('short insensible à la casse et aux espaces ; mcq comparé par index', function (): void {
    // '  quebec  ' (casse + espaces) accepté ; mcq index 1 correct.
    $r = f2Score(f2ClozeItem(4), [0 => '  quebec  ', 1 => 1]);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);

    // mcq par mauvais index → un seul trou bon.
    $r2 = f2Score(f2ClozeItem(4), [0 => 'Quebec', 1 => 2]);
    expect($r2['percent'])->toBe(50);
});

test('réponses manquantes → 0 sans erreur', function (): void {
    $empty = f2Score(f2ClozeItem(4), []);
    expect($empty['points_earned'])->toBe(0);
    expect($empty['percent'])->toBe(0);

    // null (aucune réponse) → 0, jamais d'exception.
    $nullGiven = QuizService::score([f2ClozeItem(4)], ['0' => null]);
    expect($nullGiven['percent'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Rétrocompat : autres types inchangés + round mixte (qcm + cloze)
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

test('round mixte qcm + cloze noté correctement', function (): void {
    $qcm = [
        'type'     => 'qcm',
        'question' => 'Capitale ?',
        'choices'  => ['Québec', 'Montréal'],
        'correct'  => 0,
        'points'   => 2,
    ];
    $cloze = f2ClozeItem(4);

    // Tout correct (qcm = 0, cloze = trous justes).
    $all = QuizService::score([$qcm, $cloze], ['0' => 0, '1' => [0 => 'Québec', 1 => 1]]);
    expect($all['points_possible'])->toBe(6);
    expect($all['points_earned'])->toBe(6);
    expect($all['percent'])->toBe(100);
    expect($all['correct'])->toBe(2);

    // QCM bon, cloze à moitié : 2 + round(0.5*4) = 4 / 6 ≈ 67 %.
    $mix = QuizService::score([$qcm, $cloze], ['0' => 0, '1' => [0 => 'Québec', 1 => 0]]);
    expect($mix['points_earned'])->toBe(4);
    expect($mix['percent'])->toBe(67);
    expect($mix['correct'])->toBe(1); // seul le qcm est « sans faute »
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Éditeur (Livewire) : enregistrement, validation, hydratation
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : un cloze enregistre payload text + blanks (short + mcq)', function (): void {
    $instructor = f2Instructor();
    $cat        = f2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'cloze')
        ->set('qPrompt', 'Complétez la phrase')
        ->set('qClozeText', 'La capitale est [[1]] et compte [[2]] habitants.')
        ->set('qClozeBlanks', [
            ['kind' => 'short', 'accepted' => 'Québec, Quebec', 'display' => '', 'choices' => '', 'correct' => 0],
            ['kind' => 'mcq', 'accepted' => '', 'display' => '', 'choices' => "500 000\n1 million\n5 millions", 'correct' => 1],
        ])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->type)->toBe('cloze');
    expect($q->payload['text'])->toBe('La capitale est [[1]] et compte [[2]] habitants.');
    expect($q->payload['blanks'][0]['kind'])->toBe('short');
    expect($q->payload['blanks'][0]['accepted'])->toBe(['Québec', 'Quebec']);
    expect($q->payload['blanks'][1]['kind'])->toBe('mcq');
    expect($q->payload['blanks'][1]['choices'])->toBe(['500 000', '1 million', '5 millions']);
    expect($q->payload['blanks'][1]['correct'])->toBe(1);
});

test('éditeur : texte sans marqueur [[n]] → erreur de validation', function (): void {
    $instructor = f2Instructor();
    $cat        = f2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'cloze')
        ->set('qPrompt', 'Sans trou')
        ->set('qClozeText', 'Une phrase sans aucun trou.')
        ->set('qClozeBlanks', [
            ['kind' => 'short', 'accepted' => 'Québec', 'display' => '', 'choices' => '', 'correct' => 0],
        ])
        ->call('saveQuestion')
        ->assertHasErrors('qClozeText');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : un trou short sans réponse acceptée → erreur', function (): void {
    $instructor = f2Instructor();
    $cat        = f2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'cloze')
        ->set('qPrompt', 'Trou vide')
        ->set('qClozeText', 'La capitale est [[1]].')
        ->set('qClozeBlanks', [
            ['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0],
        ])
        ->call('saveQuestion')
        ->assertHasErrors('qClozeBlanks');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : un trou mcq avec moins de 2 choix → erreur', function (): void {
    $instructor = f2Instructor();
    $cat        = f2Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'cloze')
        ->set('qPrompt', 'mcq incomplet')
        ->set('qClozeText', 'La lettre est [[1]].')
        ->set('qClozeBlanks', [
            ['kind' => 'mcq', 'accepted' => '', 'display' => '', 'choices' => 'Seul', 'correct' => 0],
        ])
        ->call('saveQuestion')
        ->assertHasErrors('qClozeBlanks');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : édition recharge texte + trous (hydratation)', function (): void {
    $instructor = f2Instructor();
    $cat        = f2Category($instructor);

    $q = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $instructor->id,
        'type'        => 'cloze',
        'prompt'      => 'À éditer',
        'payload'     => [
            'text'   => 'Le ciel est [[1]] et il fait [[2]].',
            'blanks' => [
                ['kind' => 'short', 'accepted' => ['bleu', 'azur']],
                ['kind' => 'mcq', 'choices' => ['chaud', 'froid'], 'correct' => 0],
            ],
        ],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $q->id)
        ->assertSet('qType', 'cloze')
        ->assertSet('qClozeText', 'Le ciel est [[1]] et il fait [[2]].')
        ->assertSet('qClozeBlanks', [
            ['kind' => 'short', 'accepted' => 'bleu, azur', 'display' => '', 'choices' => '', 'correct' => 0],
            ['kind' => 'mcq', 'accepted' => '', 'display' => '', 'choices' => "chaud\nfroid", 'correct' => 0],
        ]);
});
