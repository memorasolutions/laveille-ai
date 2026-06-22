<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

/**
 * Adaptateur minimal autour de QtService (Modules/Tools).
 *
 * VERDICT (audité 2026-06-20) : QtService est FIGÉ sur la banque GLOBALE. Ses
 * méthodes publiques (newRound, dailyRound) n'acceptent aucun paramètre et lisent
 * des fichiers de données PHP codés en dur (qt-questions.php, qt-truefalse.php,
 * qt-shortanswer.php) via loadData() privé. Il n'expose donc PAS de banque
 * arbitraire par clé. On l'utilise tel quel via newRound() ; $bankKey est conservé
 * (signature + payload) mais N'EST PAS encore honoré pour le choix des questions.
 *
 * Ce qui FONCTIONNE déjà côté serveur, indépendamment de la banque :
 *  - passing_score   → QuizController::submitQuiz applique le seuil de réussite.
 *  - attempts_allowed → QuizController::startQuiz applique la limite de tentatives.
 *
 * TODO RD-A2 (« quiz propre au cours ») : pour une vraie banque par cours, deux
 * pistes sûres, sans casser l'existant :
 *   1. Refactorer QtService pour accepter un chemin/clé de fichier dynamique
 *      (newRound(?string $bankKey)), avec repli sur la banque globale.
 *   2. OU stocker les questions directement dans payload['questions'] et bâtir un
 *      petit moteur local ici (score() sait déjà noter qcm/vraifaux/court/appariement).
 * Tant que ce n'est pas fait, buildRound() retombe TOUJOURS sur la banque globale.
 */
final class QuizService
{
    /**
     * Construit un round de questions pour un item quiz Academy.
     *
     * @param  string $bankKey  Clé de banque (payload['qt_bank_key']) : ignorée par QtService
     *                          (banque globale figée), conservée pour extension future (RD-A2).
     * @return array<int, array<string, mixed>>
     */
    public static function buildRound(string $bankKey): array
    {
        if (! class_exists(\Modules\Tools\Services\QtService::class)) {
            return [];
        }

        try {
            return \Modules\Tools\Services\QtService::newRound();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Calcule le score d'une soumission de quiz.
     *
     * V1-c - SCORING PONDÉRÉ : chaque question porte un champ `points` (défaut 1 si
     * absent → comportement strictement identique au comptage simple historique).
     *   - points_earned   = somme des points des questions correctes ;
     *   - points_possible = somme des points de toutes les questions ;
     *   - percent         = round(points_earned / points_possible * 100).
     * Les clés existantes sont CONSERVÉES :
     *   - score   = points obtenus (= points_earned) ;
     *   - correct = NB de bonnes réponses (inchangé, sert le badge « sans faute ») ;
     *   - total   = NB de questions (inchangé) ;
     *   - percent = désormais pondéré (identique au simple si tous les points valent 1).
     *
     * @param  array<int, array<string, mixed>> $questions  Questions du round (telles que retournées par buildRound)
     * @param  array<string, mixed>             $answers    Réponses indexées par numéro de question (clés string "0","1",…)
     * @return array{score: int, total: int, percent: int, correct: int, wrong: int, points_earned: int, points_possible: int, details: array<int, array{correct: bool, expected: mixed, given: mixed}>}
     */
    public static function score(array $questions, array $answers): array
    {
        $total = count($questions);

        if ($total === 0) {
            return [
                'score'           => 0,
                'total'           => 0,
                'percent'         => 0,
                'correct'         => 0,
                'wrong'           => 0,
                'points_earned'   => 0,
                'points_possible' => 0,
                'details'         => [],
            ];
        }

        $correct        = 0;
        $pointsEarned   = 0;
        $pointsPossible = 0;
        $details        = [];

        foreach ($questions as $index => $question) {
            $type      = $question['type'] ?? null;
            $given     = $answers[(string) $index] ?? null;
            $expected  = null;
            $isCorrect = false;

            // Pondération : `points` explicite (>= 1) sinon 1 (rétrocompat stricte).
            $points = isset($question['points']) && (int) $question['points'] >= 1
                ? (int) $question['points']
                : 1;
            $pointsPossible += $points;

            switch ($type) {
                case 'qcm':
                case 'vraifaux':
                    $expected  = (int) ($question['correct'] ?? -1);
                    $givenInt  = is_numeric($given) ? (int) $given : -1;
                    $isCorrect = $givenInt === $expected;
                    $given     = $givenInt;
                    break;

                case 'court':
                    $expected         = $question['accepted'] ?? [];
                    $givenStr         = is_string($given) ? trim(mb_strtolower($given, 'UTF-8')) : '';
                    $normalizedAccepted = array_map(
                        fn ($s) => trim(mb_strtolower((string) $s, 'UTF-8')),
                        (array) $expected
                    );
                    $isCorrect = in_array($givenStr, $normalizedAccepted, true);
                    break;

                case 'appariement':
                    $expected    = $question['answer'] ?? [];
                    $givenArr    = is_array($given) ? array_values(array_map('intval', $given)) : [];
                    $expectedArr = is_array($expected) ? array_values(array_map('intval', $expected)) : [];
                    $isCorrect   = $givenArr === $expectedArr;
                    $given       = $givenArr;
                    $expected    = $expectedArr;
                    break;

                default:
                    $isCorrect = false;
                    break;
            }

            if ($isCorrect) {
                $correct++;
                $pointsEarned += $points;
            }

            $details[$index] = [
                'correct'  => $isCorrect,
                'expected' => $expected,
                'given'    => $given,
            ];
        }

        $wrong = $total - $correct;
        // Percent PONDÉRÉ. Garde-fou : si possible vaut 0 (ne devrait pas arriver,
        // chaque question pèse >= 1), on évite la division par zéro.
        $percent = $pointsPossible > 0
            ? (int) round(($pointsEarned / $pointsPossible) * 100)
            : 0;

        return [
            'score'           => $pointsEarned, // points obtenus (= points_earned)
            'total'           => $total,        // NB de questions (inchangé)
            'percent'         => $percent,      // pondéré
            'correct'         => $correct,      // NB de bonnes réponses (badge « sans faute »)
            'wrong'           => $wrong,
            'points_earned'   => $pointsEarned,
            'points_possible' => $pointsPossible,
            'details'         => $details,
        ];
    }
}
