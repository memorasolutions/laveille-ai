<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-c SCORING PONDÉRÉ (QuizService::score).
 *
 * Prouve que :
 *  - le percent est PONDÉRÉ par les points de chaque question ;
 *  - sans points → comptage simple identique à avant (rétrocompat stricte) ;
 *  - points_earned / points_possible exacts ;
 *  - le critère « sans faute » reste basé sur correct/total (jamais les points).
 *
 * Autonome : helpers préfixés v1c. SKIPPED si Academy off.
 */

declare(strict_types=1);

use Modules\Academy\Services\QuizService;

uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
});

/** Crée un round de QCM (1 bonne réponse = index 0) avec des points donnés. */
function v1cQcmRound(array $pointsList): array
{
    $round = [];
    foreach ($pointsList as $p) {
        $item = [
            'type'     => 'qcm',
            'question' => 'Q',
            'choices'  => ['Bonne', 'Mauvaise'],
            'correct'  => 0,
        ];
        if ($p !== null) {
            $item['points'] = $p;
        }
        $round[] = $item;
    }

    return $round;
}

/** Réponses : index par numéro de question → choix (0 = bonne). */
function v1cAnswers(array $choices): array
{
    $a = [];
    foreach ($choices as $i => $c) {
        $a[(string) $i] = $c;
    }

    return $a;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Percent pondéré : points 1,2,3 → bonne #1 (1pt) + bonne #2 (2pt) = 3/6 = 50 %
// ─────────────────────────────────────────────────────────────────────────────

test('le percent est pondéré par les points de chaque question', function (): void {
    $round = v1cQcmRound([1, 2, 3]);
    // Q0 (1pt) bonne, Q1 (2pt) bonne, Q2 (3pt) mauvaise.
    $answers = v1cAnswers([0, 0, 1]);

    $r = QuizService::score($round, $answers);

    expect($r['points_possible'])->toBe(6);
    expect($r['points_earned'])->toBe(3);   // 1 + 2
    expect($r['percent'])->toBe(50);         // 3 / 6
    expect($r['correct'])->toBe(2);          // nb de bonnes réponses
    expect($r['total'])->toBe(3);            // nb de questions
    expect($r['score'])->toBe(3);            // score = points obtenus
});

test('une seule bonne réponse à forte pondération domine le percent', function (): void {
    $round = v1cQcmRound([1, 1, 10]);
    // Seule Q2 (10pt) bonne.
    $answers = v1cAnswers([1, 1, 0]);

    $r = QuizService::score($round, $answers);

    expect($r['points_possible'])->toBe(12);
    expect($r['points_earned'])->toBe(10);
    expect($r['percent'])->toBe(83);         // round(10/12*100)
    expect($r['correct'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Rétrocompat : sans points, comportement = comptage simple
// ─────────────────────────────────────────────────────────────────────────────

test('sans points le percent est identique au comptage simple (rétrocompat)', function (): void {
    $round = v1cQcmRound([null, null, null, null]); // aucun champ points
    // 3 bonnes sur 4.
    $answers = v1cAnswers([0, 0, 0, 1]);

    $r = QuizService::score($round, $answers);

    expect($r['points_possible'])->toBe(4);  // défaut 1 par question
    expect($r['points_earned'])->toBe(3);
    expect($r['percent'])->toBe(75);         // identique au 3/4 historique
    expect($r['correct'])->toBe(3);
    expect($r['total'])->toBe(4);
});

test('round vide → tous zéros', function (): void {
    $r = QuizService::score([], []);

    expect($r['percent'])->toBe(0);
    expect($r['points_earned'])->toBe(0);
    expect($r['points_possible'])->toBe(0);
    expect($r['correct'])->toBe(0);
    expect($r['total'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Badge « sans faute » : correct === total même avec barème en points
// ─────────────────────────────────────────────────────────────────────────────

test('toutes les réponses bonnes → correct égale total et percent 100 quel que soit le barème', function (): void {
    $round   = v1cQcmRound([1, 5, 2]);
    $answers = v1cAnswers([0, 0, 0]); // toutes bonnes

    $r = QuizService::score($round, $answers);

    expect($r['percent'])->toBe(100);
    expect($r['points_earned'])->toBe(8);
    expect($r['points_possible'])->toBe(8);
    // Critère « sans faute » : correct === total (indépendant des points).
    expect($r['correct'])->toBe(3);
    expect($r['total'])->toBe(3);
    expect($r['correct'])->toBe($r['total']);
});

test('un point invalide (0 ou négatif) retombe sur 1 (défensif)', function (): void {
    $round   = v1cQcmRound([0, -3]); // valeurs invalides → traitées comme 1
    $answers = v1cAnswers([0, 0]);   // toutes bonnes

    $r = QuizService::score($round, $answers);

    expect($r['points_possible'])->toBe(2);
    expect($r['points_earned'])->toBe(2);
    expect($r['percent'])->toBe(100);
});
