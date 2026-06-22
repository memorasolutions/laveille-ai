<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Socle de la banque de questions réutilisable (QB1). Tire N questions ACTIVES
 * d'une catégorie (+ sous-catégories) et les TRADUIT au format exact attendu par
 * QuizService::score() — le scoring n'est PAS dupliqué, il reste dans QuizService.
 *
 * Décision « pool » (MVP) : AUCUNE table de pool ici. Le lien item-quiz↔catégorie
 * sera porté par le payload de l'item quiz en QB2 (ex. payload['question_bank'] =
 * ['category_id' => …, 'draw_count' => …]), exactement comme qt_bank_key l'est
 * déjà aujourd'hui. Raison : zéro nouvelle table/jointure, cohérent avec le
 * stockage existant des paramètres de quiz (passing_score, attempts_allowed,
 * qt_bank_key) dans lesson_items.payload, et réversible sans migration. Une table
 * academy_quiz_pools serait justifiée seulement si un même item devait tirer de
 * PLUSIEURS catégories avec des quotas distincts — hors périmètre MVP.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

final class QuestionBankService
{
    /**
     * Tire aléatoirement N questions ACTIVES d'une catégorie, au format QuizService::score().
     *
     * @param  bool     $includeSubcategories  Inclure les sous-catégories (tirage récursif).
     * @param  int|null $seed                   Graine optionnelle → tirage DÉTERMINISTE (tests).
     * @return array<int, array<string, mixed>>  Round prêt pour QuizService::score().
     */
    public static function drawFromCategory(
        QuestionCategory $cat,
        int $n,
        bool $includeSubcategories = true,
        ?int $seed = null
    ): array {
        if ($n <= 0) {
            return [];
        }

        $categoryIds = $includeSubcategories
            ? $cat->descendantIds()
            : [(int) $cat->getKey()];

        $questions = Question::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->whereIn('type', Question::TYPES)
            ->get()
            ->all();

        if ($questions === []) {
            return [];
        }

        // Tirage : déterministe si un seed est fourni (tests), aléatoire sinon.
        if ($seed !== null) {
            mt_srand($seed);
            usort($questions, fn (): int => mt_rand(-1, 1));
            mt_srand();
        } else {
            shuffle($questions);
        }

        // Borné : si moins de N disponibles, on retourne ce qui existe (pas d'erreur).
        $picked = array_slice($questions, 0, $n);

        $round = [];
        foreach ($picked as $question) {
            $mapped = self::mapToRoundItem($question);
            if ($mapped !== null) {
                $round[] = $mapped;
            }
        }

        return $round;
    }

    /**
     * Traduit une Question (type + payload) vers la structure d'un item de round
     * telle que QuizService::score() l'attend. Retourne null si le payload est
     * inexploitable (défensif : aucune exception, jamais d'item invalide noté).
     *
     * @return array<string, mixed>|null
     */
    private static function mapToRoundItem(Question $question): ?array
    {
        $payload     = is_array($question->payload) ? $question->payload : [];
        $prompt      = (string) $question->prompt;
        $explanation = (string) ($question->explanation ?? '');
        $difficulty  = (string) ($question->difficulty ?? 'moyen');

        // V1-c : pondération. On utilise le `points` EXPLICITE de la question (1..100)
        // s'il est défini et valide ; sinon repli sur le calcul historique par
        // difficulté (rétrocompat des banques antérieures à la colonne points).
        $explicitPoints = is_numeric($question->points ?? null) ? (int) $question->points : 0;
        $points         = $explicitPoints >= 1
            ? min(100, $explicitPoints)
            : self::pointsFromDifficulty($difficulty);

        $base = [
            'theme'       => 'banque',
            'difficulty'  => $difficulty,
            'question'    => $prompt,
            'explanation' => $explanation,
            'fiche'       => null,
        ];

        switch ($question->type) {
            case 'mcq':
                $choices = array_values((array) ($payload['choices'] ?? []));
                if (count($choices) < 2) {
                    return null;
                }
                $correct = (int) ($payload['correct'] ?? 0);
                if ($correct < 0 || $correct >= count($choices)) {
                    return null;
                }

                return $base + [
                    'type'    => 'qcm',
                    'choices' => $choices,
                    'correct' => $correct,
                    'points'  => $points,
                ];

            case 'truefalse':
                // payload['answer'] = true (Vrai) | false (Faux).
                $isTrue  = (bool) ($payload['answer'] ?? false);
                $choices = ['Vrai', 'Faux'];

                return $base + [
                    'type'    => 'vraifaux',
                    'choices' => $choices,
                    'correct' => $isTrue ? 0 : 1,
                    'points'  => $points,
                ];

            case 'short':
                $accepted = array_values(array_filter(
                    (array) ($payload['accepted'] ?? []),
                    fn ($v): bool => is_string($v) && $v !== ''
                ));
                if ($accepted === []) {
                    return null;
                }

                return $base + [
                    'type'     => 'court',
                    'accepted' => $accepted,
                    'display'  => (string) ($payload['display'] ?? ''),
                    'points'   => $points,
                ];

            case 'matching':
                // payload['pairs'] = [['term' => …, 'def' => …], …] (≥ 2 paires).
                $pairs = array_values(array_filter(
                    (array) ($payload['pairs'] ?? []),
                    fn ($p): bool => is_array($p)
                        && isset($p['term'], $p['def'])
                        && (string) $p['term'] !== ''
                        && (string) $p['def'] !== ''
                ));
                if (count($pairs) < 2) {
                    return null;
                }

                $terms = array_map(fn ($p): string => (string) $p['term'], $pairs);
                $defs  = array_map(fn ($p): string => (string) $p['def'], $pairs);

                // Mélange déterministe-friendly des définitions + index attendus.
                $defsShuffled = $defs;
                if (count($defsShuffled) > 1) {
                    shuffle($defsShuffled);
                }

                $answer = [];
                foreach ($defs as $d) {
                    $idx      = array_search($d, $defsShuffled, true);
                    $answer[] = ($idx === false) ? 0 : (int) $idx;
                }

                return $base + [
                    'type'    => 'appariement',
                    'terms'   => $terms,
                    'defs'    => $defsShuffled,
                    'answer'  => $answer,
                    'points'  => $points,
                ];

            default:
                return null;
        }
    }

    private static function pointsFromDifficulty(string $difficulty): int
    {
        $d = strtolower($difficulty);

        return $d === 'facile' ? 1 : ($d === 'difficile' ? 3 : 2);
    }
}
