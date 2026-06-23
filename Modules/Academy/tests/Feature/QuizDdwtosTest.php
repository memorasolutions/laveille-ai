<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - GLISSER-DÉPOSER SUR TEXTE (mots à glisser dans des trous, type Moodle
 * « Drag and drop into text » / ddwtos).
 *
 * Prouve que :
 *  - BANQUE : une question `ddwtos` (payload text à marqueurs [[n]] + pool `words` +
 *    `answers`) devient un item de round `type=glisser-texte` dont les `segments`
 *    d'affichage N'EXPOSENT PAS le corrigé ; `options` = pool MÉLANGÉ partagé ; le corrigé
 *    `answers` (index du bon mot dans le pool mélangé) reste serveur.
 *  - SCORING (crédit partiel par trou) : tous corrects → points pleins (fraction 1 + badge
 *    sans-faute) ; 1/2 → 0,5 ; aucun → 0 ; distracteur ou trou vide → faux.
 *  - SÉCURITÉ : corrigé non exposé avant soumission ; marqueur [[n]] dupliqué géré.
 *  - RÉTROCOMPAT : les 8 types restent reconnus ; un round mixte (qcm + glisser-texte)
 *    est noté juste.
 *  - ÉDITEUR : validation (>= 1 trou ; pool >= nb de trous ; chaque trou pointe un mot
 *    valide) + enregistrement + hydratation.
 *
 * Autonome : helpers préfixés f4 (aucune redéclaration). SKIPPED si Academy off.
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
// Helpers f4 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Un item de round GLISSER-TEXTE direct (sans passer par la banque) : 2 trous, pool de
 * 4 mots (2 distracteurs). options[1] = « chat » (trou 0), options[2] = « souris » (trou 1).
 *
 * @return array<string, mixed>
 */
function f4DdwtosItem(int $points = 4): array
{
    return [
        'type'     => 'glisser-texte',
        'question' => 'Complétez la phrase',
        'segments' => [
            ['type' => 'text',  'value' => 'Le '],
            ['type' => 'blank', 'index' => 0],
            ['type' => 'text',  'value' => ' mange la '],
            ['type' => 'blank', 'index' => 1],
            ['type' => 'text',  'value' => '.'],
        ],
        'options' => ['fromage', 'chat', 'souris', 'chien'],
        'answers' => [0 => 1, 1 => 2], // trou 0 → « chat », trou 1 → « souris »
        'points'  => $points,
    ];
}

/** Score un round mono-question de glisser-texte avec le tableau de réponses par trou. */
function f4Score(array $item, array $given): array
{
    return QuizService::score([$item], ['0' => $given]);
}

function f4Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function f4Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque ddwtos',
        'position'  => 0,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Banque : tirage → segments + pool mélangé, corrigé non exposé
// ─────────────────────────────────────────────────────────────────────────────

test('banque : une question ddwtos devient un item glisser-texte (segments sans corrigé, pool mélangé)', function (): void {
    $owner = f4Instructor();
    $cat   = f4Category($owner);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'ddwtos',
        'prompt'      => 'Complétez',
        'payload'     => [
            'text'    => 'Le [[1]] mange la [[2]].',
            'words'   => ['chat', 'souris', 'chien', 'fromage'], // 2 distracteurs
            'answers' => [0 => 0, 1 => 1], // trou 0 → « chat » (index 0), trou 1 → « souris » (index 1)
        ],
        'difficulty'  => 'moyen',
        'points'      => 6,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 7);

    expect($round)->toHaveCount(1);
    $item = $round[0];

    expect($item['type'])->toBe('glisser-texte');
    expect($item['points'])->toBe(6);
    expect($item['options'])->toBeArray()->toHaveCount(4); // pool complet (distracteurs inclus)
    expect($item['answers'])->toBeArray()->toHaveCount(2);
    expect($item['segments'])->toBeArray();

    // Les SEGMENTS d'affichage ne doivent JAMAIS porter le corrigé.
    $blankSegments = array_values(array_filter(
        $item['segments'],
        fn ($s): bool => ($s['type'] ?? '') === 'blank'
    ));
    expect($blankSegments)->toHaveCount(2);
    foreach ($blankSegments as $seg) {
        expect($seg)->not->toHaveKey('answer');
        expect($seg)->not->toHaveKey('correct');
        expect(array_keys($seg))->toBe(['type', 'index']);
    }

    // Le corrigé pointe le BON mot dans le pool MÉLANGÉ.
    expect($item['options'][$item['answers'][0]])->toBe('chat');
    expect($item['options'][$item['answers'][1]])->toBe('souris');

    // Soumettre les bons index (par trou) → 100 %.
    $r = QuizService::score([$item], ['0' => $item['answers']]);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Scoring : crédit partiel par trou
// ─────────────────────────────────────────────────────────────────────────────

test('tous les trous corrects → fraction 1 → points pleins + badge sans-faute', function (): void {
    $r = f4Score(f4DdwtosItem(4), [0 => 1, 1 => 2]);

    expect($r['points_possible'])->toBe(4);
    expect($r['points_earned'])->toBe(4);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe($r['total']); // 1 / 1
});

test('1 trou sur 2 correct → fraction 0,5 → demi-points', function (): void {
    // trou 0 bon (1), trou 1 faux (index 1 = « chat » au lieu de 2 = « souris »).
    $r = f4Score(f4DdwtosItem(4), [0 => 1, 1 => 1]);

    expect($r['points_earned'])->toBe(2); // round(0.5 * 4)
    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);        // pas « sans faute »
});

test('aucun trou correct → 0', function (): void {
    $r = f4Score(f4DdwtosItem(4), [0 => 0, 1 => 3]); // « fromage » et « chien »

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);
});

test('un distracteur choisi → trou faux', function (): void {
    // trou 0 → index 0 (« fromage », un distracteur), trou 1 bon (2).
    $r = f4Score(f4DdwtosItem(4), [0 => 0, 1 => 2]);

    expect($r['percent'])->toBe(50);
    expect($r['correct'])->toBe(0);
});

test('réponses manquantes / vides → 0 sans erreur', function (): void {
    $empty = f4Score(f4DdwtosItem(4), []);
    expect($empty['points_earned'])->toBe(0);
    expect($empty['percent'])->toBe(0);

    // null (aucune réponse) → 0, jamais d'exception.
    $nullGiven = QuizService::score([f4DdwtosItem(4)], ['0' => null]);
    expect($nullGiven['percent'])->toBe(0);

    // Un seul trou rempli (l'autre vide) → demi-crédit.
    $partial = f4Score(f4DdwtosItem(4), [0 => 1]);
    expect($partial['percent'])->toBe(50);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Sécurité : corrigé non exposé + marqueur dupliqué géré
// ─────────────────────────────────────────────────────────────────────────────

test('sécurité : le rendu (partial) liste tout le pool sans révéler le bon mot', function (): void {
    $item = f4DdwtosItem(4);

    $html = view('academy::livewire.partials.ddwtos-inputs', [
        'segments'   => $item['segments'],
        'options'    => $item['options'],
        'namePrefix' => 'answers[0]',
        'legend'     => 'Ma question',
    ])->render();

    expect($html)->toContain('<fieldset');
    expect($html)->toContain('<legend');
    expect($html)->toContain('name="answers[0][0]"');
    expect($html)->toContain('name="answers[0][1]"');
    expect($html)->toContain('aria-label="Trou 1"');
    expect($html)->toContain('aria-label="Trou 2"');
    // Tout le pool est présenté (les 4 mots), donc rien ne distingue le bon mot.
    foreach ($item['options'] as $word) {
        expect($html)->toContain($word);
    }
});

test('sécurité : marqueur [[1]] dupliqué → un seul trou jouable (anti-biais)', function (): void {
    $owner = f4Instructor();
    $cat   = f4Category($owner);

    // Créé EN DIRECT (bypass éditeur) : prouve la défense côté service au tirage.
    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'ddwtos',
        'prompt'      => 'Doublon',
        'payload'     => [
            'text'    => 'Le [[1]] et encore [[1]].',
            'words'   => ['chat', 'chien'],
            'answers' => [0 => 0],
        ],
        'difficulty'  => 'moyen',
        'points'      => 2,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 7);
    expect($round)->toHaveCount(1);

    // Un SEUL segment blank (le 2e [[1]] ne crée pas un 2e champ au même name).
    $blankSegs = array_values(array_filter(
        $round[0]['segments'],
        fn ($s): bool => ($s['type'] ?? '') === 'blank'
    ));
    expect($blankSegs)->toHaveCount(1);

    // Le marqueur dupliqué est rendu en TEXTE littéral.
    $literal = array_filter(
        $round[0]['segments'],
        fn ($s): bool => ($s['type'] ?? '') === 'text' && str_contains((string) ($s['value'] ?? ''), '[[1]]')
    );
    expect($literal)->not->toBeEmpty();

    // Le scoring n'évalue qu'un trou → 1 bonne réponse = 100 %.
    $item = $round[0];
    $r    = QuizService::score([$item], ['0' => $item['answers']]);
    expect($r['percent'])->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Rétrocompat : 8 types reconnus + round mixte (qcm + glisser-texte)
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : les 8 types restent reconnus', function (): void {
    expect(Question::TYPES)->toBe([
        'mcq', 'truefalse', 'short', 'matching', 'ordering', 'cloze', 'numerical', 'ddwtos',
    ]);
});

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

test('round mixte qcm + glisser-texte noté correctement', function (): void {
    $qcm = [
        'type'     => 'qcm',
        'question' => 'Capitale ?',
        'choices'  => ['Québec', 'Montréal'],
        'correct'  => 0,
        'points'   => 2,
    ];
    $ddw = f4DdwtosItem(4);

    // Tout correct.
    $all = QuizService::score([$qcm, $ddw], ['0' => 0, '1' => [0 => 1, 1 => 2]]);
    expect($all['points_possible'])->toBe(6);
    expect($all['points_earned'])->toBe(6);
    expect($all['percent'])->toBe(100);
    expect($all['correct'])->toBe(2);

    // QCM bon, glisser à moitié : 2 + round(0.5*4) = 4 / 6 ≈ 67 %.
    $mix = QuizService::score([$qcm, $ddw], ['0' => 0, '1' => [0 => 1, 1 => 1]]);
    expect($mix['points_earned'])->toBe(4);
    expect($mix['percent'])->toBe(67);
    expect($mix['correct'])->toBe(1); // seul le qcm est « sans faute »
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Éditeur (Livewire) : enregistrement, validation, hydratation
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : un ddwtos enregistre payload text + words + answers', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Complétez la phrase')
        ->set('qDdwtosText', 'Le [[1]] mange la [[2]].')
        ->set('qDdwtosWords', ['chat', 'souris', 'chien', 'fromage'])
        ->set('qDdwtosAnswers', [0 => 0, 1 => 1])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->type)->toBe('ddwtos');
    expect($q->payload['text'])->toBe('Le [[1]] mange la [[2]].');
    expect($q->payload['words'])->toBe(['chat', 'souris', 'chien', 'fromage']);
    expect($q->payload['answers'])->toBe([0 => 0, 1 => 1]);
});

test('éditeur : texte sans marqueur [[n]] → erreur', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Sans trou')
        ->set('qDdwtosText', 'Une phrase sans aucun trou.')
        ->set('qDdwtosWords', ['chat', 'chien'])
        ->set('qDdwtosAnswers', [])
        ->call('saveQuestion')
        ->assertHasErrors('qDdwtosText');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : pool plus petit que le nombre de trous → erreur', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Pool trop petit')
        ->set('qDdwtosText', '[[1]] [[2]] [[3]].')
        ->set('qDdwtosWords', ['chat', 'chien']) // 2 mots pour 3 trous
        ->set('qDdwtosAnswers', [0 => 0, 1 => 1, 2 => 0])
        ->call('saveQuestion')
        ->assertHasErrors('qDdwtosWords');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : un trou pointant un mot invalide → erreur', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Mot invalide')
        ->set('qDdwtosText', 'Le [[1]].')
        ->set('qDdwtosWords', ['chat', 'chien'])
        ->set('qDdwtosAnswers', [0 => 9]) // hors plage du pool
        ->call('saveQuestion')
        ->assertHasErrors('qDdwtosAnswers');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : marqueur [[1]] en double → rejeté', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Doublon')
        ->set('qDdwtosText', 'Le [[1]] ou [[1]].')
        ->set('qDdwtosWords', ['chat', 'chien'])
        ->set('qDdwtosAnswers', [0 => 0])
        ->call('saveQuestion')
        ->assertHasErrors('qDdwtosText');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : édition recharge texte + pool + désignations (hydratation)', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    $q = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $instructor->id,
        'type'        => 'ddwtos',
        'prompt'      => 'À éditer',
        'payload'     => [
            'text'    => 'Le ciel est [[1]] et la mer est [[2]].',
            'words'   => ['bleu', 'vert', 'rouge'],
            'answers' => [0 => 0, 1 => 1],
        ],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $q->id)
        ->assertSet('qType', 'ddwtos')
        ->assertSet('qDdwtosText', 'Le ciel est [[1]] et la mer est [[2]].')
        ->assertSet('qDdwtosWords', ['bleu', 'vert', 'rouge'])
        ->assertSet('qDdwtosAnswers', [0 => 0, 1 => 1]);
});

test('éditeur : enregistrement → tirage → scoring de bout en bout', function (): void {
    $instructor = f4Instructor();
    $cat        = f4Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Phrase')
        ->set('qDdwtosText', 'Le [[1]] mange la [[2]].')
        ->set('qDdwtosWords', ['chat', 'souris', 'chien', 'fromage'])
        ->set('qDdwtosAnswers', [0 => 0, 1 => 1])
        ->set('qPoints', 4)
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 3);
    expect($round)->toHaveCount(1);
    $item = $round[0];

    // Bonnes réponses (lues du corrigé serveur) → 100 % ; un mauvais index → 50 %.
    $perfect = QuizService::score([$item], ['0' => $item['answers']]);
    expect($perfect['percent'])->toBe(100);

    $wrongIdx = ($item['answers'][1] === 0) ? 1 : 0;
    $half     = QuizService::score([$item], ['0' => [0 => $item['answers'][0], 1 => $wrongIdx]]);
    expect($half['percent'])->toBe(50);
});
