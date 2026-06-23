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

        // M2 — on ne charge QUE les colonnes nécessaires à mapToRoundItem() (mémoire
        // réduite sur de grosses banques) SANS toucher au chemin de tirage : le shuffle
        // PHP seedé reste IDENTIQUE (donc DÉTERMINISTE quand $seed est fourni — les
        // tests F1/QB en dépendent). On NE remplace PAS par inRandomOrder() (qui
        // casserait la reproductibilité par seed).
        $questions = Question::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->whereIn('type', Question::TYPES)
            ->select(['id', 'category_id', 'owner_id', 'type', 'prompt', 'payload', 'explanation', 'difficulty', 'points', 'is_active'])
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

        // V1-a (rétroactions multi-couches) : on RECOPIE dans l'item du round :
        //  - general_feedback = explanation de la question (« feedback général » Moodle,
        //    affiché quel que soit juste/faux) - on RÉUTILISE le champ existant, sans le
        //    dupliquer côté banque ;
        //  - choice_feedback (mcq/vraifaux) = textes de rétroaction par choix, normalisés.
        // Ces clés sont ainsi SNAPSHOTÉES dans QuizAttempt → disponibles à la révision.
        $base = [
            'theme'            => 'banque',
            'difficulty'       => $difficulty,
            'question'         => $prompt,
            'explanation'      => $explanation,
            'general_feedback' => $explanation,
            'fiche'            => null,
        ];

        switch ($question->type) {
            case 'mcq':
                $choices = array_values((array) ($payload['choices'] ?? []));
                if (count($choices) < 2) {
                    return null;
                }

                // V1-e - QCM À RÉPONSES MULTIPLES. Représentation banque :
                //   simple : payload['correct']      = int (1 bonne réponse) ;
                //   multi  : payload['multiple']     = true
                //            payload['correct_set']  = TABLEAU d'indices (>= 1 bonne).
                // L'item de round reflète ce sous-cas : `multiple` = true + `correct`
                // = TABLEAU d'indices (le scoring lit le drapeau pour le crédit partiel).
                if (! empty($payload['multiple'])) {
                    $correctSet = array_values(array_unique(array_filter(
                        array_map('intval', (array) ($payload['correct_set'] ?? [])),
                        fn (int $idx): bool => $idx >= 0 && $idx < count($choices)
                    )));
                    if ($correctSet === []) {
                        return null; // au moins une bonne réponse exigée.
                    }

                    return $base + [
                        'type'            => 'qcm',
                        'multiple'        => true,
                        'choices'         => $choices,
                        'correct'         => $correctSet,
                        'choice_feedback' => self::normalizeChoiceFeedback($payload['choice_feedback'] ?? [], count($choices)),
                        'points'          => $points,
                    ];
                }

                $correct = (int) ($payload['correct'] ?? 0);
                if ($correct < 0 || $correct >= count($choices)) {
                    return null;
                }

                return $base + [
                    'type'            => 'qcm',
                    'choices'         => $choices,
                    'correct'         => $correct,
                    'choice_feedback' => self::normalizeChoiceFeedback($payload['choice_feedback'] ?? [], count($choices)),
                    'points'          => $points,
                ];

            case 'truefalse':
                // payload['answer'] = true (Vrai) | false (Faux).
                $isTrue  = (bool) ($payload['answer'] ?? false);
                $choices = ['Vrai', 'Faux'];

                return $base + [
                    'type'            => 'vraifaux',
                    'choices'         => $choices,
                    'correct'         => $isTrue ? 0 : 1,
                    // 2 entrées : index 0 = Vrai, index 1 = Faux (alignées sur $choices).
                    'choice_feedback' => self::normalizeChoiceFeedback($payload['choice_feedback'] ?? [], 2),
                    'points'          => $points,
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

            case 'ordering':
                // ORDONNANCEMENT (« mettre dans le bon ordre », type Moodle « Ordering »).
                // Représentation banque : payload['items'] = TABLEAU des éléments dans le
                // BON ordre (index 0 = position 1, index 1 = position 2, …). On présente
                // à l'étudiant les éléments dans un ordre MÉLANGÉ (comme l'appariement
                // mélange les définitions), tout en gardant la correspondance vers l'ordre
                // correct via `answer` :
                //   - `elements` = libellés dans l'ordre AFFICHÉ (mélangé) ;
                //   - `answer`   = pour chaque élément AFFICHÉ, sa position absolue
                //                  correcte (0-based) dans l'ordre attendu.
                // Le scoring compare la position absolue choisie pour chaque élément à
                // `answer` (crédit partiel). Les bonnes réponses (ordre correct) ne sont
                // jamais exposées au client : seul `elements` est rendu ; `answer` reste
                // serveur (session) et n'est lu qu'au scoring/à la révision.
                $items = array_values(array_map(
                    fn ($v): string => (string) $v,
                    array_filter(
                        (array) ($payload['items'] ?? []),
                        fn ($v): bool => is_string($v) && trim($v) !== ''
                    )
                ));
                if (count($items) < 2) {
                    return null;
                }

                $n = count($items);

                // Permutation d'affichage (indices d'origine = positions absolues correctes).
                $order = range(0, $n - 1);
                if ($n > 1) {
                    shuffle($order);
                    // Garde-fou anti-trivial : si le mélange retombe sur l'ordre correct
                    // (l'ordre serait alors visuellement révélé), on applique une rotation
                    // d'un cran (dérangement complet, aucun élément à sa place). Ne touche
                    // pas la correspondance `answer` (calculée à partir de $order ensuite).
                    if ($order === range(0, $n - 1)) {
                        $first = array_shift($order);
                        $order[] = $first;
                    }
                }

                $elements = [];
                $answer   = [];
                foreach ($order as $originalIndex) {
                    $elements[] = $items[$originalIndex];
                    $answer[]   = (int) $originalIndex; // position absolue correcte (0-based)
                }

                return $base + [
                    'type'     => 'ordonnancement',
                    'elements' => $elements,
                    'answer'   => $answer,
                    'points'   => $points,
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

    /**
     * V1-a : normalise le tableau de rétroaction par choix vers un tableau indexé
     * 0..($count-1) de chaînes (un texte par choix, '' si aucun). Tolère un tableau
     * partiel / désordonné / surnuméraire (on ne conserve QUE les index valides).
     * Si tous les textes sont vides → []  (rétrocompat : aucune clé parasite).
     *
     * @param  mixed  $raw  Valeur brute du payload (attendu : array index => texte).
     * @return array<int, string>
     */
    private static function normalizeChoiceFeedback(mixed $raw, int $count): array
    {
        if (! is_array($raw) || $count <= 0) {
            return [];
        }

        $out      = array_fill(0, $count, '');
        $hasAny   = false;

        foreach ($raw as $index => $text) {
            $i = (int) $index;
            if ($i < 0 || $i >= $count) {
                continue;
            }
            $value = is_string($text) ? trim($text) : '';
            if ($value !== '') {
                $out[$i] = $value;
                $hasAny  = true;
            }
        }

        return $hasAny ? $out : [];
    }
}
