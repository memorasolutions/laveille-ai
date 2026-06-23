<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - RÉPONSE NUMÉRIQUE (type Moodle « Numerical » : valeur chiffrée avec
 * tolérance ± et unité facultative indicative).
 *
 * Prouve que :
 *  - BANQUE : une question `numerical` (payload correct/tolerance/unit) devient un item
 *    de round `type=numerique`. correct/tolerance vivent dans l'item (SERVEUR, comme
 *    `accepted` pour `court`) pour le scoring ; ils ne sont jamais rendus dans le HTML.
 *  - SCORING (binaire avec tolérance) : abs(donné - correct) <= tolerance → points
 *    pleins + badge sans-faute ; hors tolérance → 0. Parsing tolérant (virgule OU point
 *    décimal, espaces / milliers). Réponse vide / non numérique → 0 (jamais 500).
 *    L'unité n'est PAS notée (choix documenté : score sur la valeur seule).
 *  - RÉTROCOMPAT : les 7 autres types inchangés ; un round mixte (qcm + numerique)
 *    noté juste.
 *  - ÉDITEUR : validation (réponse numérique requise ; tolérance >= 0) + enregistrement
 *    + hydratation à l'édition.
 *
 * Autonome : helpers préfixés f3 (aucune redéclaration). SKIPPED si Academy off.
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
// Helpers f3 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Un item de round NUMÉRIQUE direct (sans passer par la banque).
 *
 * @return array<string, mixed>
 */
function f3NumItem(float $correct = 42.0, float $tolerance = 0.5, string $unit = 'km', int $points = 3): array
{
    return [
        'type'      => 'numerique',
        'question'  => 'Quelle est la distance ?',
        'correct'   => $correct,
        'tolerance' => $tolerance,
        'unit'      => $unit,
        'points'    => $points,
    ];
}

/** Score un round mono-question numérique avec la réponse de l'étudiant. */
function f3Score(array $item, mixed $given): array
{
    return QuizService::score([$item], ['0' => $given]);
}

function f3Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function f3Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque numérique',
        'position'  => 0,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Banque : tirage → item numerique (unit affichée, corrigés serveur)
// ─────────────────────────────────────────────────────────────────────────────

test('banque : une question numérique devient un item numerique', function (): void {
    $owner = f3Instructor();
    $cat   = f3Category($owner);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'numerical',
        'prompt'      => 'Distance Montréal–Québec ?',
        'payload'     => [
            'correct'   => 42,
            'tolerance' => 0.5,
            'unit'      => 'km',
        ],
        'difficulty'  => 'moyen',
        'points'      => 5,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 11);

    expect($round)->toHaveCount(1);
    $item = $round[0];

    expect($item['type'])->toBe('numerique');
    expect($item['points'])->toBe(5);
    expect($item['unit'])->toBe('km');

    // correct/tolerance vivent dans l'item (SERVEUR : nécessaires au scoring, jamais
    // rendus au client) — float, comme `accepted` l'est pour `court`.
    expect($item['correct'])->toBe(42.0);
    expect($item['tolerance'])->toBe(0.5);

    // La bonne valeur → 100 %.
    $r = QuizService::score([$item], ['0' => '42']);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

test('banque : tolerance absente → 0 par défaut ; correct non numérique → question ignorée', function (): void {
    $owner = f3Instructor();
    $cat   = f3Category($owner);

    // Sans tolérance : défaut 0 (réponse exacte exigée).
    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'numerical',
        'prompt'      => 'Combien de continents ?',
        'payload'     => ['correct' => 7],
        'difficulty'  => 'facile',
        'points'      => 1,
        'is_active'   => true,
    ]);

    $round = QuestionBankService::drawFromCategory($cat, 1, true, 3);
    expect($round)->toHaveCount(1);
    expect($round[0]['tolerance'])->toBe(0.0);
    expect($round[0]['unit'])->toBe('');

    // Une question dont le payload n'a pas de `correct` numérique n'est PAS jouable
    // (mapToRoundItem renvoie null → exclue du round, défensif).
    $cat2 = QuestionCategory::create([
        'owner_id' => $owner->id, 'parent_id' => null, 'name' => 'Cassée', 'position' => 1,
    ]);
    Question::create([
        'category_id' => $cat2->id,
        'owner_id'    => $owner->id,
        'type'        => 'numerical',
        'prompt'      => 'Sans réponse',
        'payload'     => ['correct' => 'abc'],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);
    expect(QuestionBankService::drawFromCategory($cat2, 1, true, 3))->toHaveCount(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Scoring : binaire avec tolérance
// ─────────────────────────────────────────────────────────────────────────────

test('valeur exacte (42) → points pleins + badge sans-faute', function (): void {
    $r = f3Score(f3NumItem(42.0, 0.5, 'km', 3), '42');

    expect($r['points_possible'])->toBe(3);
    expect($r['points_earned'])->toBe(3);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe($r['total']); // 1 / 1 = sans faute
});

test('valeur dans la tolérance (42,4 pour 42 ± 0,5) → points pleins', function (): void {
    $r = f3Score(f3NumItem(42.0, 0.5), '42.4');

    expect($r['points_earned'])->toBe(3);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

test('valeur hors tolérance (43 pour 42 ± 0,5) → 0', function (): void {
    $r = f3Score(f3NumItem(42.0, 0.5), '43');

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);
});

test('virgule décimale acceptée (« 42,0 ») → points pleins', function (): void {
    $r = f3Score(f3NumItem(42.0, 0.5), '42,0');

    expect($r['points_earned'])->toBe(3);
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

test('séparateurs de milliers et espaces tolérés (« 1 234,5 » = 1234,5)', function (): void {
    $item = f3NumItem(1234.5, 0.0, '', 2);

    expect(f3Score($item, '1 234,5')['percent'])->toBe(100);
    expect(f3Score($item, '1234.5')['percent'])->toBe(100);
    expect(f3Score($item, "1\u{00A0}234,5")['percent'])->toBe(100); // espace insécable
});

test('réponse vide / non numérique → 0 sans erreur (pas de 500)', function (): void {
    $item = f3NumItem(42.0, 0.5);

    expect(f3Score($item, '')['percent'])->toBe(0);
    expect(f3Score($item, 'abc')['percent'])->toBe(0);
    expect(f3Score($item, null)['percent'])->toBe(0);
    expect(f3Score($item, [])['percent'])->toBe(0);

    foreach (['', 'abc', null, []] as $bad) {
        $r = f3Score($item, $bad);
        expect($r['points_earned'])->toBe(0);
        expect($r['correct'])->toBe(0);
    }
});

test('l\'unité n\'est pas notée : la bonne valeur sans unité tapée reste correcte', function (): void {
    // L'étudiant tape seulement « 42 » (pas « 42 km ») → correct (score sur la valeur).
    $r = f3Score(f3NumItem(42.0, 0.5, 'km', 3), '42');
    expect($r['percent'])->toBe(100);
    expect($r['correct'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Rétrocompat : autres types inchangés + round mixte (qcm + numerique)
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

    $bad = QuizService::score([$item], ['0' => 1]);
    expect($bad['percent'])->toBe(0);
});

test('round mixte qcm + numerique noté correctement', function (): void {
    $qcm = [
        'type'     => 'qcm',
        'question' => 'Capitale ?',
        'choices'  => ['Québec', 'Montréal'],
        'correct'  => 0,
        'points'   => 2,
    ];
    $num = f3NumItem(42.0, 0.5, 'km', 4);

    // Tout correct : 2 + 4 = 6 / 6.
    $all = QuizService::score([$qcm, $num], ['0' => 0, '1' => '42,3']);
    expect($all['points_possible'])->toBe(6);
    expect($all['points_earned'])->toBe(6);
    expect($all['percent'])->toBe(100);
    expect($all['correct'])->toBe(2);

    // QCM bon, numérique hors tolérance : 2 / 6 ≈ 33 %.
    $mix = QuizService::score([$qcm, $num], ['0' => 0, '1' => '50']);
    expect($mix['points_earned'])->toBe(2);
    expect($mix['percent'])->toBe(33);
    expect($mix['correct'])->toBe(1); // seul le qcm est « sans faute »
});

test('le mélange laisse une question numérique intacte', function (): void {
    $num = f3NumItem(42.0, 0.5, 'km', 3);
    $shuffled = QuizService::shuffleRound([$num], false, true);

    expect($shuffled[0])->toBe($num); // renvoyée telle quelle (non concernée par V1-d)
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Éditeur (Livewire) : enregistrement, validation, hydratation
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : une question numérique enregistre correct + tolerance + unit', function (): void {
    $instructor = f3Instructor();
    $cat        = f3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Quelle est la distance ?')
        ->set('qNumericalCorrect', '42')
        ->set('qNumericalTolerance', '0,5')
        ->set('qNumericalUnit', 'km')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->type)->toBe('numerical');
    // Cast float : un entier flottant (42.0) revient en int après aller-retour JSON
    // (json_encode laisse tomber « .0 ») ; mapToRoundItem recaste en float au tirage.
    expect((float) $q->payload['correct'])->toBe(42.0);
    expect((float) $q->payload['tolerance'])->toBe(0.5);  // virgule décimale acceptée à la saisie
    expect($q->payload['unit'])->toBe('km');
});

test('éditeur : sans unité ni tolérance → tolérance 0, pas de clé unit', function (): void {
    $instructor = f3Instructor();
    $cat        = f3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Combien ?')
        ->set('qNumericalCorrect', '7')
        ->set('qNumericalTolerance', '')
        ->set('qNumericalUnit', '')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect((float) $q->payload['correct'])->toBe(7.0);
    expect((float) $q->payload['tolerance'])->toBe(0.0);
    expect($q->payload)->not->toHaveKey('unit');
});

test('éditeur : réponse correcte manquante / non numérique → erreur', function (): void {
    $instructor = f3Instructor();
    $cat        = f3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Sans réponse')
        ->set('qNumericalCorrect', 'abc')
        ->call('saveQuestion')
        ->assertHasErrors('qNumericalCorrect');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : tolérance négative → erreur', function (): void {
    $instructor = f3Instructor();
    $cat        = f3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Tolérance négative')
        ->set('qNumericalCorrect', '10')
        ->set('qNumericalTolerance', '-1')
        ->call('saveQuestion')
        ->assertHasErrors('qNumericalTolerance');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('éditeur : édition d\'une question numérique hydrate le formulaire', function (): void {
    $instructor = f3Instructor();
    $cat        = f3Category($instructor);

    $q = Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $instructor->id,
        'type'        => 'numerical',
        'prompt'      => 'À éditer',
        'payload'     => ['correct' => 3.14, 'tolerance' => 0.01, 'unit' => 'm'],
        'difficulty'  => 'moyen',
        'points'      => 2,
        'is_active'   => true,
    ]);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $q->id)
        ->assertSet('qType', 'numerical')
        ->assertSet('qNumericalCorrect', '3.14')
        ->assertSet('qNumericalTolerance', '0.01')
        ->assertSet('qNumericalUnit', 'm');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Parseur partagé (locale) — DRY entre score() et l'éditeur
// ─────────────────────────────────────────────────────────────────────────────

test('parseNumber : tolère virgule, point et milliers ; null si non numérique', function (): void {
    expect(QuizService::parseNumber('42'))->toBe(42.0);
    expect(QuizService::parseNumber('42,5'))->toBe(42.5);
    expect(QuizService::parseNumber('42.5'))->toBe(42.5);
    expect(QuizService::parseNumber('1 234,5'))->toBe(1234.5);
    expect(QuizService::parseNumber('1,234.5'))->toBe(1234.5); // point décimal, virgule milliers
    expect(QuizService::parseNumber('-3,14'))->toBe(-3.14);
    expect(QuizService::parseNumber(42))->toBe(42.0);
    expect(QuizService::parseNumber(''))->toBeNull();
    expect(QuizService::parseNumber('abc'))->toBeNull();
    expect(QuizService::parseNumber(null))->toBeNull();
    expect(QuizService::parseNumber([]))->toBeNull();
});
