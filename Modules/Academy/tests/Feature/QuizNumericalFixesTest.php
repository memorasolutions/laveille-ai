<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — CORRECTIFS AUDIT F3 (réponse numérique). Couvre :
 *  - C1 : overflow INF. parseNumber('1e309') → null ; une question correct='1e309'
 *    est rejetée proprement (pas de 500 / corruption) ; une réponse étudiant '1e309'
 *    est notée 0 et les `details` restent json-encodables.
 *  - C2 : longueur de l'unité validée serveur (max 40, contournable via maxlength HTML).
 *  - C3 : l'input numérique est rendu via le PARTIAL DRY en différé ET immédiat,
 *    en préservant name / id / aria-label / inputmode / required (rendu équivalent).
 *  - Rétrocompat : un numérique normal (42 ± 0,5) reste noté comme avant.
 *
 * Autonome : helpers préfixés fix3 (aucune redéclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
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
// Helpers fix3 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function fix3Instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function fix3Category(User $owner): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => 'Banque F3',
        'position'  => 0,
    ]);
}

/** @return array<string, mixed> */
function fix3NumItem(float $correct = 42.0, float $tolerance = 0.5, string $unit = 'km', int $points = 3): array
{
    return [
        'type'      => 'numerique',
        'question'  => 'Combien ?',
        'correct'   => $correct,
        'tolerance' => $tolerance,
        'unit'      => $unit,
        'points'    => $points,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// C1 — overflow INF
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : parseNumber renvoie null pour les overflows (INF/-INF)', function (): void {
    expect(QuizService::parseNumber('1e309'))->toBeNull();
    expect(QuizService::parseNumber('-1e309'))->toBeNull();
    expect(QuizService::parseNumber('1E309'))->toBeNull();
    // Un float déjà infini en entrée est aussi neutralisé.
    expect(QuizService::parseNumber(INF))->toBeNull();
    expect(QuizService::parseNumber(-INF))->toBeNull();
    expect(QuizService::parseNumber(NAN))->toBeNull();
    // Les valeurs finies normales restent intactes (non-régression).
    expect(QuizService::parseNumber('42'))->toBe(42.0);
    expect(QuizService::parseNumber('1e3'))->toBe(1000.0);
});

test('C1 : créer une question avec correct=1e309 est rejeté proprement (pas de 500)', function (): void {
    $instructor = fix3Instructor();
    $cat        = fix3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Overflow')
        ->set('qNumericalCorrect', '1e309')
        ->call('saveQuestion')
        ->assertHasErrors('qNumericalCorrect');

    // Aucune question corrompue créée (json_encode aurait échoué sur INF).
    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('C1 : une réponse étudiant 1e309 est notée 0 et les details restent json-encodables', function (): void {
    $item = fix3NumItem(42.0, 0.5, 'km', 3);

    $r = QuizService::score([$item], ['0' => '1e309']);

    expect($r['points_earned'])->toBe(0);
    expect($r['percent'])->toBe(0);
    expect($r['correct'])->toBe(0);

    // INF ne pollue pas les détails : la valeur normalisée donnée est null (pas INF).
    expect($r['details'][0]['given'])->toBeNull();

    // Les détails doivent être encodables sans erreur (INF ferait échouer json_encode).
    $json = json_encode($r['details'], JSON_THROW_ON_ERROR);
    expect($json)->toBeString();
    expect(str_contains($json, 'inf'))->toBeFalse();
});

test('C1 : un payload numérique corrompu (1e309) est exclu du round (non jouable)', function (): void {
    $owner = fix3Instructor();
    $cat   = fix3Category($owner);

    // Insertion directe d'un payload corrompu (contourne la validation éditeur).
    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'numerical',
        'prompt'      => 'Corrompue',
        'payload'     => ['correct' => '1e309'],
        'difficulty'  => 'moyen',
        'points'      => 1,
        'is_active'   => true,
    ]);

    expect(\Modules\Academy\Services\QuestionBankService::drawFromCategory($cat, 1, true, 3))->toHaveCount(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// C2 — longueur de l'unité validée serveur
// ─────────────────────────────────────────────────────────────────────────────

test('C2 : une unité de 100 caractères est rejetée (max 40)', function (): void {
    $instructor = fix3Instructor();
    $cat        = fix3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Unité trop longue')
        ->set('qNumericalCorrect', '42')
        ->set('qNumericalUnit', str_repeat('x', 100))
        ->call('saveQuestion')
        ->assertHasErrors('qNumericalUnit');

    expect(Question::where('category_id', $cat->id)->count())->toBe(0);
});

test('C2 : une unité <= 40 caractères passe (non-régression)', function (): void {
    $instructor = fix3Instructor();
    $cat        = fix3Category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Unité ok')
        ->set('qNumericalCorrect', '42')
        ->set('qNumericalUnit', 'kilomètres')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->firstOrFail();
    expect($q->payload['unit'])->toBe('kilomètres');
});

// ─────────────────────────────────────────────────────────────────────────────
// C3 — partial DRY : différé ET immédiat (rendu équivalent)
// ─────────────────────────────────────────────────────────────────────────────

test('C3 : le partial numérique rend l\'input en mode différé (name/id/aria/inputmode/required)', function (): void {
    $html = view('academy::livewire.partials.numerical-input', [
        'nameAttr' => 'answers[0]',
        'inputId'  => 'q5_0_num',
        'unit'     => 'km',
    ])->render();

    expect($html)->toContain('name="answers[0]"');
    expect($html)->toContain('id="q5_0_num"');
    expect($html)->toContain('inputmode="decimal"');
    expect($html)->toContain('aria-label="Réponse numérique en km"');
    expect($html)->toContain('required');
    expect($html)->toContain('km'); // unité indicative affichée
});

test('C3 : le partial numérique rend l\'input en mode immédiat (name/id préservés)', function (): void {
    $html = view('academy::livewire.partials.numerical-input', [
        'nameAttr' => 'answer',
        'inputId'  => 'iq5_0_num',
        'unit'     => 'km',
    ])->render();

    expect($html)->toContain('name="answer"');
    expect($html)->toContain('id="iq5_0_num"');
    expect($html)->toContain('inputmode="decimal"');
    expect($html)->toContain('aria-label="Réponse numérique en km"');
    expect($html)->toContain('required');
});

test('C3 : rendu équivalent différé/immédiat (seuls name et id diffèrent)', function (): void {
    $deferred = view('academy::livewire.partials.numerical-input', [
        'nameAttr' => 'answers[0]', 'inputId' => 'q5_0_num', 'unit' => 'km',
    ])->render();
    $immediate = view('academy::livewire.partials.numerical-input', [
        'nameAttr' => 'answer', 'inputId' => 'iq5_0_num', 'unit' => 'km',
    ])->render();

    // En neutralisant name + id, les deux rendus doivent être identiques.
    $norm = fn (string $h): string => preg_replace(
        ['/name="[^"]*"/', '/id="[^"]*"/'],
        ['name="X"', 'id="X"'],
        $h
    );

    expect($norm($deferred))->toBe($norm($immediate));
});

test('C3 : sans unité, aucun « en … » dans l\'aria-label ni de span d\'unité', function (): void {
    $html = view('academy::livewire.partials.numerical-input', [
        'nameAttr' => 'answers[1]',
        'inputId'  => 'q5_1_num',
        'unit'     => '',
    ])->render();

    expect($html)->toContain('aria-label="Réponse numérique"');
    expect($html)->not->toContain('aria-label="Réponse numérique en');
});

// ─────────────────────────────────────────────────────────────────────────────
// Rétrocompat — numérique normal noté comme avant
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : 42 ± 0,5 reste noté exactement comme avant', function (): void {
    $item = fix3NumItem(42.0, 0.5, 'km', 3);

    expect(QuizService::score([$item], ['0' => '42'])['percent'])->toBe(100);
    expect(QuizService::score([$item], ['0' => '42,4'])['percent'])->toBe(100);
    expect(QuizService::score([$item], ['0' => '43'])['percent'])->toBe(0);
});

test('rétrocompat : formatNumber retire zéros et point superflus', function (): void {
    expect(QuizService::formatNumber(42.5))->toBe('42.5');
    expect(QuizService::formatNumber(7.0))->toBe('7');
    expect(QuizService::formatNumber(3.14))->toBe('3.14');
    expect(QuizService::formatNumber(0.0))->toBe('0');
});
