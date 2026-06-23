<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-d - RESTRICTIONS D'ACCES PAR ITEM (parité Moodle « Restrict access »).
 * SOURCE UNIQUE (DRY) de l'évaluation des conditions d'accès d'un item de leçon.
 * 100 % SERVEUR : aucune condition n'est exposée au client, zéro contournement.
 *
 * Structure payload['access_restrictions'] :
 *   {
 *     "match": "all"|"any",   // ET ou OU, défaut "all"
 *     "conditions": [
 *       { "type": "date",       "from": "2026-07-01T00:00:00+00:00", "until": "...", "hide": false },
 *       { "type": "grade",      "item_id": 42, "min_percent": 70, "hide": false },
 *       { "type": "completion", "item_id": 41, "hide": false },
 *       { "type": "group",      "group_id": 3, "hide": true },
 *     ]
 *   }
 *
 *   hide (par condition) :
 *     - false (défaut) = item GRISÉ avec raison(s) lisibles (style Moodle).
 *     - true = item MASQUÉ entièrement quand au moins UNE condition hide=true
 *              n'est pas remplie.
 *
 * RETROCOMPATIBILITE STRICTE : un item sans la clé 'access_restrictions' dans son
 * payload retourne toujours allowed=true, hidden=false, reasons=[].
 *
 * ANTI-IDOR : les item_id référencés dans les conditions grade/completion sont
 * re-scopés au cours courant (un item d'un autre cours ne compte jamais).
 *
 * Groupes (type "group") : supporté grâce au modèle Cohort existant
 * (table academy_cohorts, pivot academy_cohort_user).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Carbon\Carbon;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LessonItem;

final class AccessRestrictionService
{
    /** Types de conditions autorisées (liste blanche, ordre préférentiel pour l'UI). */
    public const TYPES = ['date', 'grade', 'completion', 'group'];

    /**
     * Évalue toutes les restrictions d'un item pour un utilisateur.
     *
     * @return array{allowed: bool, hidden: bool, reasons: string[]}
     */
    public static function evaluate(User $user, LessonItem $item, Course $course): array
    {
        $payload = $item->payload ?? [];

        // Rétrocompat stricte : clé absente ou vide → toujours accessible.
        if (empty($payload['access_restrictions'])) {
            return self::open();
        }

        $config = $payload['access_restrictions'];
        if (! is_array($config)) {
            return self::open();
        }

        $conditions = $config['conditions'] ?? [];
        if (! is_array($conditions) || count($conditions) === 0) {
            return self::open();
        }

        $match = (($config['match'] ?? 'all') === 'any') ? 'any' : 'all';

        // IDs d'items valides du cours (anti-IDOR : scoped au cours courant uniquement).
        $validItemIds = self::courseItemIds($course);

        $failedReasons  = [];   // raisons des conditions non remplies
        $totalValid     = 0;    // nb de conditions syntaxiquement valides
        $totalPassed    = 0;    // nb de conditions effectivement remplies
        $hiddenBlocked  = false; // au moins une condition hide=true non remplie ?

        foreach ($conditions as $cond) {
            if (! is_array($cond)) {
                continue;
            }

            $type = (string) ($cond['type'] ?? '');
            if (! in_array($type, self::TYPES, true)) {
                continue;
            }

            $totalValid++;

            [$ok, $reason] = self::evaluateCondition($user, $type, $cond, $validItemIds);

            if ($ok) {
                $totalPassed++;
            } else {
                $failedReasons[] = $reason;
                if ((bool) ($cond['hide'] ?? false)) {
                    $hiddenBlocked = true;
                }
            }
        }

        // Aucune condition valide = pas de restriction.
        if ($totalValid === 0) {
            return self::open();
        }

        $allowed = match ($match) {
            'any'   => $totalPassed > 0,    // au moins 1 condition remplie
            default => count($failedReasons) === 0,  // toutes remplies
        };

        return [
            'allowed' => $allowed,
            'hidden'  => ! $allowed && $hiddenBlocked,
            'reasons' => $failedReasons,
        ];
    }

    /**
     * Évalue une condition individuelle.
     *
     * @param  array<string, mixed>  $cond
     * @param  array<int, int>       $validItemIds  IDs d'items du cours (anti-IDOR)
     * @return array{0: bool, 1: string}  [remplie, raison si non remplie]
     */
    private static function evaluateCondition(
        User $user,
        string $type,
        array $cond,
        array $validItemIds
    ): array {
        return match ($type) {
            'date'       => self::evalDate($cond),
            'grade'      => self::evalGrade($user, $cond, $validItemIds),
            'completion' => self::evalCompletion($user, $cond, $validItemIds),
            'group'      => self::evalGroup($user, $cond),
            default      => [true, ''],
        };
    }

    /**
     * Condition DATE : accès entre from et until (les deux sont facultatifs).
     *
     * @param  array<string, mixed>  $cond
     * @return array{0: bool, 1: string}
     */
    private static function evalDate(array $cond): array
    {
        $now = now()->timezone('America/Toronto');

        if (! empty($cond['from'])) {
            try {
                $from = Carbon::parse($cond['from'])->timezone('America/Toronto');
                if ($now->lt($from)) {
                    return [
                        false,
                        'Disponible à partir du '.$from->locale('fr')->isoFormat('D MMMM YYYY [à] H[h]mm'),
                    ];
                }
            } catch (\Throwable) {
                // Date invalide → condition ignorée (permissive envers l'étudiant)
            }
        }

        if (! empty($cond['until'])) {
            try {
                $until = Carbon::parse($cond['until'])->timezone('America/Toronto');
                if ($now->gt($until)) {
                    return [
                        false,
                        'Ce contenu n\'était disponible que jusqu\'au '.$until->locale('fr')->isoFormat('D MMMM YYYY'),
                    ];
                }
            } catch (\Throwable) {
                // Date invalide → condition ignorée
            }
        }

        return [true, ''];
    }

    /**
     * Condition GRADE : note >= min_percent % à l'item X (via QuizGradeService).
     * Anti-IDOR : item_id doit être dans $validItemIds (items du cours courant).
     *
     * @param  array<string, mixed>  $cond
     * @param  array<int, int>       $validItemIds
     * @return array{0: bool, 1: string}
     */
    private static function evalGrade(User $user, array $cond, array $validItemIds): array
    {
        $refId = (int) ($cond['item_id'] ?? 0);

        // Anti-IDOR : l'item référencé doit appartenir au cours courant.
        if ($refId <= 0 || ! in_array($refId, $validItemIds, true)) {
            return [true, ''];  // condition mal configurée → permissive
        }

        $refItem = LessonItem::find($refId);
        if ($refItem === null || $refItem->type !== 'quiz') {
            return [true, ''];  // item supprimé ou non-quiz → permissive
        }

        $minPercent = max(0, min(100, (int) ($cond['min_percent'] ?? 0)));
        $title      = (string) ($refItem->title ?? 'Quiz');

        // Utiliser QuizGradeService si disponible (calcul selon méthode de notation).
        if (class_exists(QuizGradeService::class)) {
            $grade = QuizGradeService::effectiveGrade($user->id, $refItem);
            $ok    = $grade['attempts'] > 0 && $grade['percent'] >= $minPercent;
        } else {
            // Repli direct sur QuizAttempt (ne tient pas compte de la méthode).
            $best = \Modules\Academy\Models\QuizAttempt::where('user_id', $user->id)
                ->where('lesson_item_id', $refId)
                ->where('needs_grading', false)
                ->max('percent') ?? -1;
            $ok = $best >= $minPercent;
        }

        if (! $ok) {
            return [false, 'Nécessite au moins '.$minPercent.' % au quiz « '.$title.' »'];
        }

        return [true, ''];
    }

    /**
     * Condition COMPLETION : l'item X doit être complété (Completion model).
     * Anti-IDOR : item_id doit être dans $validItemIds.
     *
     * @param  array<string, mixed>  $cond
     * @param  array<int, int>       $validItemIds
     * @return array{0: bool, 1: string}
     */
    private static function evalCompletion(User $user, array $cond, array $validItemIds): array
    {
        $refId = (int) ($cond['item_id'] ?? 0);

        if ($refId <= 0 || ! in_array($refId, $validItemIds, true)) {
            return [true, ''];  // condition mal configurée → permissive
        }

        $refItem = LessonItem::find($refId);
        if ($refItem === null) {
            return [true, ''];  // item supprimé → permissive
        }

        $completed = Completion::where('user_id', $user->id)
            ->where('lesson_item_id', $refId)
            ->where('status', 'completed')
            ->exists();

        if (! $completed) {
            return [false, 'Terminez d\'abord « '.($refItem->title ?? 'l\'élément précédent').' »'];
        }

        return [true, ''];
    }

    /**
     * Condition GROUP : l'utilisateur doit appartenir à la cohorte X.
     * Cohort est scopé au cours courant (la relation course() le garantit).
     * Supporté grâce au modèle Cohort existant (V3, table academy_cohorts).
     *
     * @param  array<string, mixed>  $cond
     * @return array{0: bool, 1: string}
     */
    private static function evalGroup(User $user, array $cond): array
    {
        if (! class_exists(Cohort::class)) {
            return [true, ''];  // fonctionnalité groupes non présente → permissive
        }

        $groupId = (int) ($cond['group_id'] ?? 0);
        if ($groupId <= 0) {
            return [true, ''];
        }

        $cohort = Cohort::find($groupId);
        if ($cohort === null) {
            return [true, ''];  // cohorte supprimée → permissive
        }

        $isMember = $cohort->members()->where('user_id', $user->id)->exists();
        if (! $isMember) {
            return [false, 'Réservé au groupe « '.$cohort->name.' »'];
        }

        return [true, ''];
    }

    /**
     * IDs de TOUS les items de leçon appartenant au cours (anti-IDOR).
     * Un item d'un autre cours ne peut jamais servir de référence dans une condition.
     *
     * @return array<int, int>
     */
    public static function courseItemIds(Course $course): array
    {
        $course->loadMissing([
            'chapters.lessons.lessonItems',
        ]);

        $ids = [];
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $lessonItem) {
                    $ids[] = (int) $lessonItem->id;
                }
            }
        }

        return array_unique($ids);
    }

    /**
     * Valide et sanitise une liste de conditions avant stockage dans le payload.
     * Retourne uniquement les conditions valides (liste blanche de type, bornes %,
     * dates cohérentes, anti-IDOR sur item_id).
     *
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  array<int, int>                   $validItemIds  items du cours (anti-IDOR)
     * @return array<int, array<string, mixed>>
     */
    public static function sanitizeConditions(array $conditions, array $validItemIds): array
    {
        $out = [];

        foreach ($conditions as $cond) {
            if (! is_array($cond)) {
                continue;
            }

            $type = (string) ($cond['type'] ?? '');
            if (! in_array($type, self::TYPES, true)) {
                continue;
            }

            $clean = [
                'type' => $type,
                'hide' => (bool) ($cond['hide'] ?? false),
            ];

            switch ($type) {
                case 'date':
                    $hasFrom  = false;
                    $hasUntil = false;

                    if (! empty($cond['from'])) {
                        try {
                            $clean['from'] = Carbon::parse($cond['from'])->toIso8601String();
                            $hasFrom       = true;
                        } catch (\Throwable) {}
                    }

                    if (! empty($cond['until'])) {
                        try {
                            $clean['until'] = Carbon::parse($cond['until'])->toIso8601String();
                            $hasUntil       = true;
                        } catch (\Throwable) {}
                    }

                    // Cohérence : from doit être strictement avant until.
                    if ($hasFrom && $hasUntil
                        && Carbon::parse($clean['from'])->gte(Carbon::parse($clean['until']))) {
                        unset($clean['until']);
                    }

                    // Une condition date sans aucune borne est inutile : on la retire.
                    if (! isset($clean['from']) && ! isset($clean['until'])) {
                        continue 2;
                    }
                    break;

                case 'grade':
                    $refId = (int) ($cond['item_id'] ?? 0);
                    if ($refId <= 0 || ! in_array($refId, $validItemIds, true)) {
                        continue 2;  // anti-IDOR ou item absent → rejeté
                    }
                    $clean['item_id']     = $refId;
                    $clean['min_percent'] = max(0, min(100, (int) ($cond['min_percent'] ?? 0)));
                    break;

                case 'completion':
                    $refId = (int) ($cond['item_id'] ?? 0);
                    if ($refId <= 0 || ! in_array($refId, $validItemIds, true)) {
                        continue 2;  // anti-IDOR ou item absent → rejeté
                    }
                    $clean['item_id'] = $refId;
                    break;

                case 'group':
                    $groupId = (int) ($cond['group_id'] ?? 0);
                    if ($groupId <= 0) {
                        continue 2;
                    }
                    $clean['group_id'] = $groupId;
                    break;
            }

            $out[] = $clean;
        }

        return array_values($out);
    }

    /**
     * Libellé court du type de condition (usage UI éditeur).
     */
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'date'       => 'Date',
            'grade'      => 'Note minimale',
            'completion' => 'Achèvement requis',
            'group'      => 'Groupe',
            default      => $type,
        };
    }

    /** Résultat d'accès ouvert (rétrocompatibilité - item sans restriction). */
    private static function open(): array
    {
        return ['allowed' => true, 'hidden' => false, 'reasons' => []];
    }
}
