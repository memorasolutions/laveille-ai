<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F17 - STATISTIQUES par question de banque. Pour un lot de questions, calcule depuis
 * l'historique reel des tentatives (academy_quiz_attempts) :
 *   - usages   : nombre d'apparitions de la question dans un questions_snapshot ;
 *   - correct  : nombre d'apparitions ou la reponse a ete jugee correcte ;
 *   - facilite : indice de facilite = % de bonnes reponses (correct / usages).
 *
 * LIEN question-banque <-> occurrences : chaque item du round porte la cle STABLE
 * `bank_question_id` (posee par QuestionBankService::mapToRoundItem). Le round est
 * snapshote tel quel dans la tentative -> on retrouve les occurrences par cet id.
 * Les questions creees AVANT cette cle (ou tirees du round QT historique) n'ont pas
 * de bank_question_id : elles apparaissent simplement « Pas encore utilisee » (zero
 * statistique), sans erreur (limite documentee, retrocompat stricte).
 *
 * La justesse n'est PAS dupliquee : on rejoue QuizService::score() UNE fois par
 * tentative pertinente (sur le snapshot + les reponses figes) et on lit son tableau
 * `details` par index. Les essais (correction manuelle, sans justesse automatique)
 * comptent comme usage mais sont EXCLUS du calcul de facilite.
 *
 * Perf : UNE requete (pre-filtrage LIKE par lot d'ids) puis agregation en PHP. Pas de
 * N+1 (aucune requete dans la boucle) ; un score() par tentative concernee seulement.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\QuizAttempt;

final class QuestionStatsService
{
    /**
     * Statistiques agregees pour un LOT de questions de banque (par id).
     *
     * @param  array<int, int>  $questionIds
     * @return array<int, array{uses: int, correct: int, facility: int|null, has_data: bool}>
     *         Map id_question => stats. Toute id demandee est presente (zero si inutilisee).
     */
    public static function forQuestions(array $questionIds): array
    {
        // Normalise + dedup les ids demandes (bornes : entiers positifs).
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $questionIds),
            fn (int $id): bool => $id > 0
        )));

        // Squelette : chaque id demandee a une entree par defaut (« pas de donnee »).
        $stats = [];
        foreach ($ids as $id) {
            $stats[$id] = ['uses' => 0, 'correct' => 0, 'facility' => null, 'has_data' => false];
        }

        if ($ids === []) {
            return $stats;
        }

        // Pre-filtrage SQL : ne charger que les tentatives dont le snapshot mentionne au
        // moins une des questions du lot. Le cast JSON stocke du JSON compact, donc le
        // motif "bank_question_id":<id> y apparait litteralement. LIKE est portable
        // (MySQL + SQLite). Garde-fou : la verification reelle (par id exact) se refait
        // en PHP ci-dessous, le LIKE ne sert qu'a reduire le volume scanne.
        $rows = QuizAttempt::query()
            ->whereNotNull('questions_snapshot')
            ->where(function ($query) use ($ids): void {
                foreach ($ids as $id) {
                    $query->orWhere('questions_snapshot', 'like', '%"bank_question_id":'.$id.'%');
                }
            })
            ->select(['id', 'answers', 'questions_snapshot'])
            ->get();

        $wanted = array_flip($ids); // lookup O(1)

        foreach ($rows as $attempt) {
            $snapshot = is_array($attempt->questions_snapshot) ? $attempt->questions_snapshot : [];
            if ($snapshot === []) {
                continue;
            }

            // Repere les index du snapshot rattaches a une question demandee.
            $relevantIndexes = [];
            foreach ($snapshot as $index => $item) {
                if (! is_array($item) || ! isset($item['bank_question_id'])) {
                    continue;
                }
                $bankId = (int) $item['bank_question_id'];
                if (isset($wanted[$bankId])) {
                    $relevantIndexes[$index] = $bankId;
                }
            }

            if ($relevantIndexes === []) {
                continue; // faux positif du LIKE (autre id) : rien a compter.
            }

            // Rejoue le scoring UNE fois pour cette tentative (justesse par index).
            $answers = is_array($attempt->answers) ? $attempt->answers : [];
            $details = self::scoreDetails($snapshot, $answers);

            foreach ($relevantIndexes as $index => $bankId) {
                $stats[$bankId]['uses']++;
                $stats[$bankId]['has_data'] = true;

                $detail = $details[$index] ?? null;

                // Essai (correction manuelle) : usage compte, mais EXCLU de la facilite
                // (aucune justesse automatique). On ne touche donc pas a `correct`.
                if (is_array($detail) && ! empty($detail['needs_grading'])) {
                    continue;
                }

                if (is_array($detail) && ! empty($detail['correct'])) {
                    $stats[$bankId]['correct']++;
                }
            }
        }

        // Indice de facilite = correct / usages SCORABLES (hors essais). On recompte les
        // usages scorables pour ne pas diluer la facilite avec des essais non notes.
        foreach ($stats as $id => $row) {
            $scorableUses = self::scorableUses($rows, $id, $wanted);
            $stats[$id]['facility'] = $scorableUses > 0
                ? (int) round(($row['correct'] / $scorableUses) * 100)
                : null;
        }

        return $stats;
    }

    /**
     * Compte les apparitions SCORABLES (non-essai) d'une question dans le jeu de
     * tentatives deja charge. Lecture seule, pas de requete (sur la collection en
     * memoire) -> aucun N+1.
     *
     * @param  \Illuminate\Support\Collection<int, QuizAttempt>  $rows
     * @param  array<int, int>                                    $wanted
     */
    private static function scorableUses($rows, int $bankId, array $wanted): int
    {
        $count = 0;

        foreach ($rows as $attempt) {
            $snapshot = is_array($attempt->questions_snapshot) ? $attempt->questions_snapshot : [];
            foreach ($snapshot as $item) {
                if (! is_array($item) || (int) ($item['bank_question_id'] ?? 0) !== $bankId) {
                    continue;
                }
                // Un essai n'entre pas dans la facilite (correction manuelle).
                if (($item['type'] ?? null) === 'essai') {
                    continue;
                }
                $count++;
            }
        }

        return $count;
    }

    /**
     * Rejoue QuizService::score() de maniere defensive (jamais d'exception remontee :
     * une tentative au snapshot corrompu ne doit pas casser tout le tableau de bord).
     *
     * @param  array<int, mixed>  $snapshot
     * @param  array<int|string, mixed>  $answers
     * @return array<int, array<string, mixed>>
     */
    private static function scoreDetails(array $snapshot, array $answers): array
    {
        try {
            $result = QuizService::score($snapshot, $answers);
        } catch (\Throwable) {
            return [];
        }

        return is_array($result['details'] ?? null) ? $result['details'] : [];
    }
}
