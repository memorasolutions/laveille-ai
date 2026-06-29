<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TESTS GOLDEN-MASTER / CARACTÉRISATION — QuestionBankManager (QB2).
 *
 * OBJECTIF : FIGER le comportement ACTUEL de QuestionBankManager AVANT toute
 * extraction en traits. Ces tests décrivent CE QUI EST. Si un comportement paraît
 * étrange, on le fige tel quel (commentaire CARACTÉRISATION). La suite doit rester
 * 100 % verte après chaque extraction.
 *
 * COUVERTURE PAR GROUPE D'EXTRACTION :
 *
 *  CAT. Catégories (→ HandlesCategories)
 *      CAT1 : startRenameCategory — charge le nom dans renamingCategory + renameCategoryName
 *      CAT2 : renameCategory — persiste le nouveau nom en base
 *      CAT3 : cancelRenameCategory — réinitialise renamingCategory et renameCategoryName
 *      CAT4 : confirmCategoryDeletion — affecte confirmingCategoryDeletion
 *      CAT5 : cancelCategoryDeletion — remet confirmingCategoryDeletion à null
 *      CAT6 : selectCategory — affecte selectedCategoryId, vide filterTagId et historyQuestionId
 *
 *  QST. Questions CRUD (→ HandlesQbQuestions)
 *      QST1 : editQuestion — hydrate les propriétés du formulaire depuis la DB
 *      QST2 : resetQuestionForm — vide le formulaire (propriétés aux défauts)
 *      QST3 : confirmQuestionDeletion — affecte confirmingQuestionDeletion
 *      QST4 : cancelQuestionDeletion — remet confirmingQuestionDeletion à null
 *
 *  REP. Repeaters payload (→ HandlesPayloadRepeaters)
 *      REP1  : addChoice — ajoute un choix vide
 *      REP2  : removeChoice — retire l'index demandé et réindexe ; minimum 2 respecté
 *      REP3  : addAccepted / removeAccepted — repeater réponses courtes
 *      REP4  : addPair / removePair — repeater appariement ; minimum 2 respecté
 *      REP5  : addOrderingItem / removeOrderingItem — repeater ordonnancement
 *      REP6  : moveOrderingItem — échange avec le voisin (up/down/bornes)
 *      REP7  : addClozeBlank / removeClozeBlank — repeater cloze
 *      REP8  : addDdwtosWord / removeDdwtosWord — repeater ddwtos + màj qDdwtosAnswers
 *
 *  BUILD. Builders de payload — types avancés (→ HandlesPayloadBuilders)
 *      BUILD1 : saveQuestion ordering — payload['items'] correct
 *      BUILD2 : saveQuestion ordering — moins de 2 éléments → erreur qOrderingItems
 *      BUILD3 : saveQuestion cloze — payload['text'] + ['blanks'] correct
 *      BUILD4 : saveQuestion cloze — texte sans marqueur → erreur qClozeText
 *      BUILD5 : saveQuestion numerical — payload['correct'] + ['tolerance']
 *      BUILD6 : saveQuestion numerical — valeur non numérique → erreur qNumericalCorrect
 *      BUILD7 : saveQuestion ddwtos — payload['text'] + ['words'] + ['answers']
 *      BUILD8 : saveQuestion essay — payload['grader_info'] ou payload vide
 *
 *  READ. Lectures + méthodes utilitaires (→ HandlesQbReads)
 *      READ1 : ddwtosBlankNumbers — extrait les numéros de trous du texte (triés, uniques)
 *      READ2 : ddwtosPool — ne retourne que les mots non vides, indexés par index d'origine
 *
 * GARDE-FOU : si le module Academy est désactivé, tous les tests sont SKIPPED.
 * PRÉFIXE helpers : `gmQBM_` (évite tout conflit avec les autres fichiers de test).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers préfixés gmQBM_ (autonomes, sans conflit avec les autres test files)
// ─────────────────────────────────────────────────────────────────────────────

function gmQBM_instructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

function gmQBM_category(User $owner, string $name = 'Catégorie GM'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => $name,
        'position'  => 0,
    ]);
}

function gmQBM_question(QuestionCategory $cat, User $owner, string $type = 'truefalse'): Question
{
    $payload = match ($type) {
        'mcq'   => ['choices' => ['A', 'B'], 'correct' => 0],
        'short' => ['accepted' => ['réponse']],
        default => ['answer' => true],
    };

    return Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => $type,
        'prompt'      => 'Énoncé doré',
        'payload'     => $payload,
        'difficulty'  => 'moyen',
        'is_active'   => true,
    ]);
}

// ═════════════════════════════════════════════════════════════════════════════
// Groupe CAT — Catégories (→ HandlesCategories)
// ═════════════════════════════════════════════════════════════════════════════

test('CAT1 : startRenameCategory charge le nom dans les propriétés de renommage', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor, 'Nom original');

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('startRenameCategory', $cat->id)
        ->assertSet('renamingCategory', $cat->id)
        ->assertSet('renameCategoryName', 'Nom original');
});

test('CAT2 : renameCategory persiste le nouveau nom en base', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor, 'Ancien nom');

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('startRenameCategory', $cat->id)
        ->set('renameCategoryName', 'Nouveau nom')
        ->call('renameCategory')
        ->assertSet('renamingCategory', null)
        ->assertSet('renameCategoryName', '');

    expect($cat->fresh()->name)->toBe('Nouveau nom');
});

test('CAT3 : cancelRenameCategory réinitialise renamingCategory et renameCategoryName', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor, 'À renommer');

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('startRenameCategory', $cat->id)
        ->call('cancelRenameCategory')
        ->assertSet('renamingCategory', null)
        ->assertSet('renameCategoryName', '');
});

test('CAT4 : confirmCategoryDeletion affecte confirmingCategoryDeletion', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('confirmCategoryDeletion', $cat->id)
        ->assertSet('confirmingCategoryDeletion', $cat->id);
});

test('CAT5 : cancelCategoryDeletion remet confirmingCategoryDeletion à null', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('confirmCategoryDeletion', $cat->id)
        ->call('cancelCategoryDeletion')
        ->assertSet('confirmingCategoryDeletion', null);
});

test('CAT6 : selectCategory affecte selectedCategoryId et vide filterTagId + historyQuestionId', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('filterTagId', 42)
        ->set('historyQuestionId', 99)
        ->call('selectCategory', $cat->id)
        ->assertSet('selectedCategoryId', $cat->id)
        ->assertSet('filterTagId', null)
        ->assertSet('historyQuestionId', null);
});

// ═════════════════════════════════════════════════════════════════════════════
// Groupe QST — Questions CRUD (→ HandlesQbQuestions)
// ═════════════════════════════════════════════════════════════════════════════

test('QST1 : editQuestion hydrate les propriétés du formulaire depuis la DB', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);
    $question   = gmQBM_question($cat, $instructor, 'truefalse');

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $question->id)
        ->assertSet('editingQuestionId', $question->id)
        ->assertSet('qType', 'truefalse')
        ->assertSet('qPrompt', 'Énoncé doré')
        ->assertSet('qDifficulty', 'moyen')
        ->assertSet('qIsActive', true);
});

test('QST2 : resetQuestionForm vide le formulaire (propriétés aux valeurs par défaut)', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);
    $question   = gmQBM_question($cat, $instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('editQuestion', $question->id)
        ->call('resetQuestionForm')
        ->assertSet('editingQuestionId', null)
        ->assertSet('qType', 'mcq')
        ->assertSet('qPrompt', '')
        ->assertSet('qExplanation', null)
        ->assertSet('qDifficulty', 'moyen')
        ->assertSet('qPoints', 1)
        ->assertSet('qIsActive', true);
});

test('QST3 : confirmQuestionDeletion affecte confirmingQuestionDeletion', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);
    $question   = gmQBM_question($cat, $instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('confirmQuestionDeletion', $question->id)
        ->assertSet('confirmingQuestionDeletion', $question->id);
});

test('QST4 : cancelQuestionDeletion remet confirmingQuestionDeletion à null', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);
    $question   = gmQBM_question($cat, $instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('confirmQuestionDeletion', $question->id)
        ->call('cancelQuestionDeletion')
        ->assertSet('confirmingQuestionDeletion', null);
});

// ═════════════════════════════════════════════════════════════════════════════
// Groupe REP — Repeaters payload (→ HandlesPayloadRepeaters)
// ═════════════════════════════════════════════════════════════════════════════

test('REP1 : addChoice ajoute un choix vide à la liste', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->assertSet('qChoices', ['', ''])
        ->call('addChoice');

    expect($lw->get('qChoices'))->toHaveCount(3);
});

test('REP2 : removeChoice retire le bon index et réindexe ; minimum 2 préservé', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('qChoices', ['A', 'B', 'C'])
        ->call('removeChoice', 1); // retire 'B'

    expect($lw->get('qChoices'))->toBe(['A', 'C']);

    // minimum 2 : un 2e removeChoice ne descend pas sous 2
    $lw->call('removeChoice', 0);
    expect($lw->get('qChoices'))->toHaveCount(2);
});

test('REP3 : addAccepted ajoute une réponse vide ; removeAccepted retire sans tomber sous 1', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('addAccepted');

    expect($lw->get('qAccepted'))->toHaveCount(2);

    $lw->call('removeAccepted', 0);
    expect($lw->get('qAccepted'))->toHaveCount(1);

    // minimum 1 : removeAccepted sur une liste à 1 élément = no-op
    $lw->call('removeAccepted', 0);
    expect($lw->get('qAccepted'))->toHaveCount(1);
});

test('REP4 : addPair ajoute une paire vide ; removePair retire sans tomber sous 2', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('addPair');

    expect($lw->get('qPairs'))->toHaveCount(3);

    $lw->call('removePair', 2);
    expect($lw->get('qPairs'))->toHaveCount(2);

    // minimum 2 : no-op
    $lw->call('removePair', 0);
    expect($lw->get('qPairs'))->toHaveCount(2);
});

test('REP5 : addOrderingItem ajoute un élément vide ; removeOrderingItem retire sans tomber sous 2', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->assertSet('qOrderingItems', ['', '', ''])
        ->call('addOrderingItem');

    expect($lw->get('qOrderingItems'))->toHaveCount(4);

    $lw->call('removeOrderingItem', 3);
    expect($lw->get('qOrderingItems'))->toHaveCount(3);

    // descendre à 2 est permis
    $lw->call('removeOrderingItem', 2);
    expect($lw->get('qOrderingItems'))->toHaveCount(2);

    // minimum 2 : no-op
    $lw->call('removeOrderingItem', 0);
    expect($lw->get('qOrderingItems'))->toHaveCount(2);
});

test('REP6 : moveOrderingItem échange avec le voisin, respecte les bornes', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('qOrderingItems', ['A', 'B', 'C']);

    // descend B (index 1 → 2)
    $lw->call('moveOrderingItem', 1, 'down');
    expect($lw->get('qOrderingItems'))->toBe(['A', 'C', 'B']);

    // remonte A (index 0) → no-op (déjà en tête)
    $lw->call('moveOrderingItem', 0, 'up');
    expect($lw->get('qOrderingItems'))->toBe(['A', 'C', 'B']);

    // remonte C (index 1 → 0)
    $lw->call('moveOrderingItem', 1, 'up');
    expect($lw->get('qOrderingItems'))->toBe(['C', 'A', 'B']);
});

test('REP7 : addClozeBlank ajoute un trou vide ; removeClozeBlank retire sans tomber sous 1', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('addClozeBlank');

    expect($lw->get('qClozeBlanks'))->toHaveCount(2);

    $lw->call('removeClozeBlank', 1);
    expect($lw->get('qClozeBlanks'))->toHaveCount(1);

    // minimum 1 : no-op
    $lw->call('removeClozeBlank', 0);
    expect($lw->get('qClozeBlanks'))->toHaveCount(1);
});

test('REP8 : addDdwtosWord ajoute ; removeDdwtosWord retire et met à jour qDdwtosAnswers', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('qDdwtosWords', ['pomme', 'poire', 'cerise'])
        ->set('qDdwtosAnswers', [0 => 2]) // trou 0 pointe le mot d'index 2 ('cerise')
        ->call('addDdwtosWord');

    expect($lw->get('qDdwtosWords'))->toHaveCount(4);

    // retire index 1 ('poire') ; l'index 2 → 1 dans la désignation ('cerise' recule)
    $lw->call('removeDdwtosWord', 1);
    expect($lw->get('qDdwtosWords'))->toHaveCount(3);
    // CARACTÉRISATION : après retrait de l'index 1, l'ancien index 2 devient index 1
    expect($lw->get('qDdwtosAnswers')[0])->toBe(1);

    // minimum 2 : no-op
    $lw->set('qDdwtosWords', ['A', 'B']);
    $lw->call('removeDdwtosWord', 0);
    expect($lw->get('qDdwtosWords'))->toHaveCount(2);
});

// ═════════════════════════════════════════════════════════════════════════════
// Groupe BUILD — Builders payload, types avancés (→ HandlesPayloadBuilders)
// ═════════════════════════════════════════════════════════════════════════════

test('BUILD1 : saveQuestion ordering — payload[items] contient les éléments non vides', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ordering')
        ->set('qPrompt', 'Ordonner les étapes.')
        ->set('qOrderingItems', ['Étape 1', 'Étape 2', 'Étape 3'])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'ordering')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['items'])->toBe(['Étape 1', 'Étape 2', 'Étape 3']);
});

test('BUILD2 : saveQuestion ordering — moins de 2 éléments non vides → erreur qOrderingItems', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ordering')
        ->set('qPrompt', 'Incomplet')
        ->set('qOrderingItems', ['Seul', ''])
        ->call('saveQuestion')
        ->assertHasErrors('qOrderingItems');

    expect(Question::where('category_id', $cat->id)->exists())->toBeFalse();
});

test('BUILD3 : saveQuestion cloze — payload contient text + blanks', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'cloze')
        ->set('qPrompt', 'Complétez le texte.')
        ->set('qClozeText', 'Le [[1]] est l\'avenir.')
        ->set('qClozeBlanks', [
            ['kind' => 'short', 'accepted' => 'numérique', 'display' => '', 'choices' => '', 'correct' => 0],
        ])
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'cloze')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['text'])->toContain('[[1]]');
    expect($q->payload['blanks'])->toHaveCount(1);
    expect($q->payload['blanks'][0]['kind'])->toBe('short');
});

test('BUILD4 : saveQuestion cloze — texte sans marqueur [[n]] → erreur qClozeText', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'cloze')
        ->set('qPrompt', 'Texte sans trou')
        ->set('qClozeText', 'Pas de trou dans ce texte')
        ->call('saveQuestion')
        ->assertHasErrors('qClozeText');

    expect(Question::where('category_id', $cat->id)->exists())->toBeFalse();
});

test('BUILD5 : saveQuestion numerical — payload correct + tolerance', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Combien font 6 × 7 ?')
        ->set('qNumericalCorrect', '42')
        ->set('qNumericalTolerance', '0')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'numerical')->first();
    expect($q)->not->toBeNull();
    expect((float) $q->payload['correct'])->toBe(42.0);
    expect((float) $q->payload['tolerance'])->toBe(0.0);
});

test('BUILD6 : saveQuestion numerical — valeur non numérique → erreur qNumericalCorrect', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'numerical')
        ->set('qPrompt', 'Réponse invalide')
        ->set('qNumericalCorrect', 'abc')
        ->call('saveQuestion')
        ->assertHasErrors('qNumericalCorrect');

    expect(Question::where('category_id', $cat->id)->exists())->toBeFalse();
});

test('BUILD7 : saveQuestion ddwtos — payload contient text, words, answers', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'ddwtos')
        ->set('qPrompt', 'Glisser les mots.')
        ->set('qDdwtosText', 'Le [[1]] est bleu.')
        ->set('qDdwtosWords', ['ciel', 'soleil', 'nuage'])
        ->set('qDdwtosAnswers', [0 => 0]) // trou 0 → mot index 0 ('ciel')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'ddwtos')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['text'])->toContain('[[1]]');
    expect($q->payload['words'])->toBe(['ciel', 'soleil', 'nuage']);
    expect($q->payload['answers'][0])->toBe(0);
});

test('BUILD8 : saveQuestion essay — payload contient grader_info ou est vide', function (): void {
    $instructor = gmQBM_instructor();
    $cat        = gmQBM_category($instructor);

    Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat->id)
        ->set('qType', 'essay')
        ->set('qPrompt', 'Rédigez votre réponse.')
        ->set('qGraderInfo', 'Critères : clarté et pertinence.')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q = Question::where('category_id', $cat->id)->where('type', 'essay')->first();
    expect($q)->not->toBeNull();
    expect($q->payload['grader_info'])->toBe('Critères : clarté et pertinence.');

    // Sans grader_info → payload vide (pas de clé inutile)
    $instructor2 = gmQBM_instructor();
    $cat2        = gmQBM_category($instructor2);

    Livewire::actingAs($instructor2)
        ->test(QuestionBankManager::class)
        ->call('selectCategory', $cat2->id)
        ->set('qType', 'essay')
        ->set('qPrompt', 'Autre essai.')
        ->set('qGraderInfo', '')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    $q2 = Question::where('category_id', $cat2->id)->where('type', 'essay')->first();
    expect($q2->payload)->toBe([]);
});

// ═════════════════════════════════════════════════════════════════════════════
// Groupe READ — Lectures utilitaires (→ HandlesQbReads)
// ═════════════════════════════════════════════════════════════════════════════

test('READ1 : ddwtosBlankNumbers extrait les numéros de trous (triés, uniques)', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('qDdwtosText', 'Le [[2]] et le [[1]] puis [[2]] encore.');

    // CARACTÉRISATION : les doublons sont éliminés, le résultat est trié.
    expect($lw->instance()->ddwtosBlankNumbers())->toBe([1, 2]);
});

test('READ2 : ddwtosPool ne retourne que les mots non vides indexés par leur index d\'origine', function (): void {
    $instructor = gmQBM_instructor();

    $lw = Livewire::actingAs($instructor)
        ->test(QuestionBankManager::class)
        ->set('qDdwtosWords', ['pomme', '', 'cerise', '  ']);

    $pool = $lw->instance()->ddwtosPool();

    // CARACTÉRISATION : index 0 et 2 seulement ; les vides/espaces sont exclus.
    expect($pool)->toHaveKey(0, 'pomme');
    expect($pool)->toHaveKey(2, 'cerise');
    expect($pool)->not->toHaveKey(1);
    expect($pool)->not->toHaveKey(3);
});
