<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Banque de questions réutilisable (QB1, socle DONNÉES, backend pur).
 *
 * Prouve que :
 *  - drawFromCategory(n) tire n questions actives, inclut les sous-catégories
 *    si demandé, exclut les inactives, et borne à ce qui existe (n > dispo) ;
 *  - le format produit passe dans QuizService::score() (round + bonnes réponses
 *    → score plein ; mauvaises réponses → score réduit) ;
 *  - descendantIds() est borné à l'arbre du MÊME propriétaire (scoping) ;
 *  - les migrations sont additives (down = drop).
 *
 * Autonome : helpers préfixés qb1, aucune redéclaration d'une fonction d'un
 * autre fichier de test.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Services\QuestionBankService;
use Modules\Academy\Services\QuizService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers qb1 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function qb1User(): User
{
    return User::factory()->create();
}

function qb1Category(User $owner, ?QuestionCategory $parent = null, string $name = 'Catégorie'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => $parent?->id,
        'name'      => $name,
        'position'  => 0,
    ]);
}

function qb1Mcq(QuestionCategory $cat, bool $active = true): Question
{
    return Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $cat->owner_id,
        'type'        => 'mcq',
        'prompt'      => 'Quelle est la capitale du Québec ?',
        'payload'     => [
            'choices' => ['Montréal', 'Québec', 'Laval', 'Gatineau'],
            'correct' => 1,
        ],
        'difficulty'  => 'facile',
        'is_active'   => $active,
    ]);
}

function qb1TrueFalse(QuestionCategory $cat): Question
{
    return Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $cat->owner_id,
        'type'        => 'truefalse',
        'prompt'      => 'Le Saint-Laurent est un fleuve.',
        'payload'     => ['answer' => true],
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);
}

function qb1Short(QuestionCategory $cat): Question
{
    return Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $cat->owner_id,
        'type'        => 'short',
        'prompt'      => 'Quel mot désigne une consigne donnée à une IA ?',
        'payload'     => ['accepted' => ['prompt', 'invite']],
        'difficulty'  => 'moyen',
        'is_active'   => true,
    ]);
}

function qb1Matching(QuestionCategory $cat): Question
{
    return Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $cat->owner_id,
        'type'        => 'matching',
        'prompt'      => 'Associe chaque terme à sa définition.',
        'payload'     => [
            'pairs' => [
                ['term' => 'IA', 'def' => 'Imitation de capacités cognitives par une machine.'],
                ['term' => 'API', 'def' => 'Interface pour relier deux logiciels.'],
                ['term' => 'GPU', 'def' => 'Processeur graphique massivement parallèle.'],
            ],
        ],
        'difficulty'  => 'difficile',
        'is_active'   => true,
    ]);
}

/**
 * Construit le tableau de réponses CORRECTES pour un round (clés "0","1",…),
 * en lisant la bonne réponse de chaque item tel que produit par le service.
 *
 * @param  array<int, array<string, mixed>> $round
 * @return array<string, mixed>
 */
function qb1CorrectAnswers(array $round): array
{
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = match ($q['type']) {
            'qcm', 'vraifaux' => (int) $q['correct'],
            'court'           => $q['accepted'][0],
            'appariement'     => $q['answer'],
            default           => null,
        };
    }

    return $answers;
}

// ─────────────────────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────────────────────

it('tire n questions actives et inclut les sous-catégories', function (): void {
    $owner  = qb1User();
    $parent = qb1Category($owner, null, 'Parent');
    $child  = qb1Category($owner, $parent, 'Enfant');

    qb1Mcq($parent);
    qb1TrueFalse($parent);
    qb1Short($child);
    qb1Matching($child);

    // Avec sous-catégories : 4 dispo → on en tire 3.
    $round = QuestionBankService::drawFromCategory($parent, 3, includeSubcategories: true, seed: 42);
    expect($round)->toHaveCount(3);

    // Sans sous-catégories : seules les 2 du parent sont visibles.
    $roundNoSub = QuestionBankService::drawFromCategory($parent, 10, includeSubcategories: false);
    expect($roundNoSub)->toHaveCount(2);
});

it('exclut les questions inactives', function (): void {
    $owner = qb1User();
    $cat   = qb1Category($owner);

    qb1Mcq($cat, active: true);
    qb1Mcq($cat, active: false);
    qb1Mcq($cat, active: false);

    $round = QuestionBankService::drawFromCategory($cat, 10);
    expect($round)->toHaveCount(1);
});

it('borne le tirage quand n dépasse le nombre disponible (sans erreur)', function (): void {
    $owner = qb1User();
    $cat   = qb1Category($owner);

    qb1Mcq($cat);
    qb1TrueFalse($cat);

    $round = QuestionBankService::drawFromCategory($cat, 50);
    expect($round)->toHaveCount(2);
});

it('retourne un tableau vide sur catégorie sans question ou n<=0', function (): void {
    $owner = qb1User();
    $cat   = qb1Category($owner);

    expect(QuestionBankService::drawFromCategory($cat, 5))->toBe([]);

    qb1Mcq($cat);
    expect(QuestionBankService::drawFromCategory($cat, 0))->toBe([]);
});

it('produit un format scoré PLEIN par QuizService::score sur bonnes réponses', function (): void {
    $owner  = qb1User();
    $parent = qb1Category($owner, null, 'Parent');
    $child  = qb1Category($owner, $parent, 'Enfant');

    qb1Mcq($parent);
    qb1TrueFalse($parent);
    qb1Short($child);
    qb1Matching($child);

    $round = QuestionBankService::drawFromCategory($parent, 4, includeSubcategories: true, seed: 7);
    expect($round)->toHaveCount(4);

    $answers = qb1CorrectAnswers($round);
    $result  = QuizService::score($round, $answers);

    expect($result['total'])->toBe(4);
    expect($result['correct'])->toBe(4);
    expect($result['percent'])->toBe(100);
});

it('réduit le score quand les réponses sont fausses', function (): void {
    $owner = qb1User();
    $cat   = qb1Category($owner);

    qb1Mcq($cat);        // bonne = index 1
    qb1TrueFalse($cat);  // bonne = Vrai (index 0)

    $round = QuestionBankService::drawFromCategory($cat, 2, includeSubcategories: false, seed: 1);
    expect($round)->toHaveCount(2);

    // Réponses volontairement fausses (index inexistant / opposé).
    $wrong = [];
    foreach ($round as $i => $q) {
        $wrong[(string) $i] = 99;
    }

    $result = QuizService::score($round, $wrong);
    expect($result['correct'])->toBe(0);
    expect($result['percent'])->toBe(0);
});

it('borne descendantIds à l’arbre du même propriétaire (scoping)', function (): void {
    $ownerA = qb1User();
    $ownerB = qb1User();

    $rootA  = qb1Category($ownerA, null, 'Racine A');
    $childA = qb1Category($ownerA, $rootA, 'Enfant A');

    // Catégorie d'un AUTRE propriétaire, raccrochée au même parent (cas limite).
    $intruder = QuestionCategory::create([
        'owner_id'  => $ownerB->id,
        'parent_id' => $rootA->id,
        'name'      => 'Intrus B',
        'position'  => 0,
    ]);

    $ids = $rootA->descendantIds();

    expect($ids)->toContain($rootA->id, $childA->id);
    expect($ids)->not->toContain($intruder->id);
});

it('le scope owned ne renvoie que les catégories du propriétaire', function (): void {
    $ownerA = qb1User();
    $ownerB = qb1User();

    qb1Category($ownerA, null, 'A1');
    qb1Category($ownerA, null, 'A2');
    qb1Category($ownerB, null, 'B1');

    expect(QuestionCategory::owned($ownerA)->count())->toBe(2);
    expect(QuestionCategory::owned($ownerB)->count())->toBe(1);
});

it('les migrations sont additives (tables présentes puis droppables)', function (): void {
    expect(Schema::hasTable('academy_question_categories'))->toBeTrue();
    expect(Schema::hasTable('academy_questions'))->toBeTrue();

    Schema::dropIfExists('academy_questions');
    Schema::dropIfExists('academy_question_categories');

    expect(Schema::hasTable('academy_questions'))->toBeFalse();
    expect(Schema::hasTable('academy_question_categories'))->toBeFalse();
});
