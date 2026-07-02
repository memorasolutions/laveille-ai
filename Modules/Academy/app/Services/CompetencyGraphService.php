<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22-b - GRAPHE DE COMPÉTENCES (relations pondérées entre compétences, parité
 * Moodle). Le référentiel F22 existant (Competency + CompetencyLink, voir
 * CompetencyService) est PLAT : chaque compétence est liée à des cours/items,
 * sans relation entre compétences elles-mêmes. Ce service AJOUTE une couche de
 * PRÉREQUIS (academy_competency_relations, Competency::requiresCompetencies())
 * sans toucher au calcul d'acquisition existant.
 *
 * ════════════════ FORMULE DE MAÎTRISE (documentée, déterministe) ════════════════
 * masteryFor(user, competency) sépare d'abord les items pertinents
 * (CompetencyService::relevantItemIds) en deux sous-ensembles DISJOINTS, par
 * type (même vocabulaire GRADABLE_TYPES que CompetencyService, DRY) :
 *   - items NOTÉS (quiz)      → signal "score de quiz"
 *   - items NON notés (autres) → signal "taux de complétion"
 * puis combine DEUX signaux déjà persistés, tous deux dérivés de statements
 * xAPI (academy_xapi_statements, voir XapiRecorderService) :
 *
 *   1. Taux de COMPLÉTION : proportion des items NON notés marqués 'completed'
 *      via un statement xAPI verb=completed/object_type=lesson (ratio
 *      complétés / total non-notés, 0..1). NULL s'il n'y a aucun item non noté.
 *   2. Score moyen de QUIZ : moyenne des result.percent (0..100, normalisé en
 *      0..1) des statements xAPI verb=attempted/object_type=quiz dont
 *      object_id ∈ items NOTÉS de la compétence, en gardant SEULEMENT la
 *      dernière tentative par item (reflète la maîtrise ACTUELLE, pas
 *      l'historique). NULL s'il n'y a aucun item noté, ou aucune tentative.
 *
 *   mastery = si aucun signal disponible (aucun item, ou aucun statement) → 0.0.
 *             si seule la complétion est disponible (aucun item noté)      → taux de complétion.
 *             si seul le quiz est disponible (aucun item non-noté)         → score moyen de quiz.
 *             sinon (les deux)  → moyenne simple (50/50) des deux signaux.
 *
 * C'est une formule SIMPLE et déterministe (pas de ML) : chaque signal pèse au
 * plus 50 %, un item non tenté compte comme 0 (pénalise, n'ignore pas). Toujours
 * bornée à [0.0, 1.0].
 *
 * isUnlocked(user, competency) : ET logique strict sur TOUS les prérequis
 * directs (requiresCompetencies) — chacun doit avoir mastery >= son propre
 * mastery_threshold. Sans prérequis déclaré, TOUJOURS déverrouillée (rétrocompat).
 *
 * DRAPEAU : config('academy.competency_graph_enabled'), défaut false. Tant que
 * désactivé, TOUTES les méthodes publiques sont NO-OP à retour NEUTRE (0.0 pour
 * masteryFor, true pour isUnlocked — ne verrouille jamais rien tant que la
 * feature est off, comportement actuel inchangé — et graphe vide pour graphFor).
 * Activer via ACADEMY_COMPETENCY_GRAPH_ENABLED=true dans le .env.
 *
 * SÉCURITÉ / PERF : lecture seule, 100 % défensif (try/catch silencieux comme
 * les autres services Academy), aucune requête réseau. Pas d'agrégation par lot
 * ici (portée = un utilisateur / une compétence / un cours à la fois, cohérent
 * avec l'usage prévu : affichage d'une fiche compétence ou d'un graphe de cours).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Competency;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\XapiStatement;

final class CompetencyGraphService
{
    /** Drapeau maître — voir config('academy.competency_graph_enabled'), défaut false. */
    public function isEnabled(): bool
    {
        return (bool) config('academy.competency_graph_enabled', false);
    }

    /**
     * Score de maîtrise [0.0, 1.0] d'un utilisateur sur une compétence, dérivé
     * des statements xAPI des items pertinents. NO-OP (0.0) si le drapeau est
     * désactivé, si la compétence n'a aucun item pertinent, ou en cas d'erreur.
     */
    public function masteryFor(User $user, Competency $competency): float
    {
        if (! $this->isEnabled()) {
            return 0.0;
        }

        try {
            $itemIds = CompetencyService::relevantItemIds($competency);
            if ($itemIds === []) {
                return 0.0;
            }

            // Sépare les items NOTÉS (quiz, cf. CompetencyService::GRADABLE_TYPES,
            // même vocabulaire DRY) des items non notés : chaque signal ne porte
            // QUE sur son propre sous-ensemble d'items (jamais les deux mélangés).
            $itemTypes  = LessonItem::whereIn('id', $itemIds)->pluck('type', 'id');
            $gradedIds  = $itemTypes->filter(fn (string $type): bool => in_array($type, CompetencyService::GRADABLE_TYPES, true))->keys()->all();
            $ungradedIds = $itemTypes->reject(fn (string $type): bool => in_array($type, CompetencyService::GRADABLE_TYPES, true))->keys()->all();

            $completionRatio = $this->completionRatio($user, $ungradedIds);
            $quizRatio        = $this->quizScoreRatio($user, $gradedIds);

            if ($completionRatio === null && $quizRatio === null) {
                return 0.0;
            }

            if ($quizRatio === null) {
                $mastery = $completionRatio;
            } elseif ($completionRatio === null) {
                $mastery = $quizRatio;
            } else {
                $mastery = ($completionRatio + $quizRatio) / 2;
            }

            return max(0.0, min(1.0, round($mastery, 4)));
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * Vrai si TOUS les prérequis directs de la compétence sont atteints à leur
     * seuil respectif (mastery_threshold). Sans prérequis déclaré → TOUJOURS
     * déverrouillée (rétrocompat stricte). NO-OP (true) si le drapeau est
     * désactivé : on ne verrouille jamais rien tant que la feature est off.
     */
    public function isUnlocked(User $user, Competency $competency): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        try {
            $prerequisites = $competency->requiresCompetencies()->get();
            if ($prerequisites->isEmpty()) {
                return true;
            }

            foreach ($prerequisites as $prerequisite) {
                $threshold = (float) $prerequisite->pivot->mastery_threshold;
                $mastery   = $this->masteryFor($user, $prerequisite);

                if ($mastery < $threshold) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Structure du graphe de compétences d'un cours, prête à consommer par une
     * vue : nœuds = compétences pertinentes du cours (CompetencyService), arêtes
     * = relations de prérequis ENTRE ces compétences (seuil + pondération).
     * NO-OP (graphe vide) si le drapeau est désactivé ou en cas d'erreur.
     *
     * @return array{nodes: array<int, array{id: int, name: string, is_active: bool}>, edges: array<int, array{from: int, to: int, mastery_threshold: float, weight: float}>}
     */
    public function graphFor(Course $course): array
    {
        $empty = ['nodes' => [], 'edges' => []];

        if (! $this->isEnabled()) {
            return $empty;
        }

        try {
            $competencies = CompetencyService::competenciesForCourse($course);
            if ($competencies->isEmpty()) {
                return $empty;
            }

            $ids = $competencies->pluck('id')->map(fn ($id): int => (int) $id)->all();

            $nodes = $competencies->map(fn (Competency $c): array => [
                'id'        => (int) $c->id,
                'name'      => (string) $c->name,
                'is_active' => (bool) $c->is_active,
            ])->values()->all();

            // Arêtes : uniquement les relations dont les DEUX bouts appartiennent
            // au cours (graphe local au cours, pas le graphe global du référentiel).
            $edges = [];
            foreach ($competencies as $competency) {
                foreach ($competency->requiresCompetencies as $prerequisite) {
                    if (! in_array((int) $prerequisite->id, $ids, true)) {
                        continue;
                    }

                    $edges[] = [
                        'from'              => (int) $prerequisite->id, // prérequis
                        'to'                => (int) $competency->id,   // dépendante
                        'mastery_threshold' => (float) $prerequisite->pivot->mastery_threshold,
                        'weight'            => (float) $prerequisite->pivot->weight,
                    ];
                }
            }

            return ['nodes' => $nodes, 'edges' => $edges];
        } catch (\Throwable) {
            return $empty;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internes (signaux de maîtrise, lecture seule)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Taux [0..1] d'items pertinents COMPLÉTÉS par l'utilisateur, dérivé des
     * statements xAPI verb=completed/object_type=lesson. NULL si aucun item
     * pertinent n'est de type "complétable" (aucun statement possible).
     *
     * @param  array<int, int>  $itemIds
     */
    private function completionRatio(User $user, array $itemIds): ?float
    {
        if ($itemIds === []) {
            return null;
        }

        $completedCount = XapiStatement::query()
            ->forUser($user->getKey())
            ->forVerb(XapiRecorderService::VERB_COMPLETED)
            ->where('object_type', XapiRecorderService::OBJECT_LESSON)
            ->whereIn('object_id', $itemIds)
            ->distinct()
            ->count('object_id');

        return count($itemIds) > 0 ? min(1.0, $completedCount / count($itemIds)) : null;
    }

    /**
     * Score moyen [0..1] des items NOTÉS pertinents, dérivé de la DERNIÈRE
     * tentative xAPI (verb=attempted/object_type=quiz) par item. NULL si
     * aucun statement de quiz n'existe pour ces items (aucun item noté tenté).
     *
     * @param  array<int, int>  $itemIds
     */
    private function quizScoreRatio(User $user, array $itemIds): ?float
    {
        if ($itemIds === []) {
            return null;
        }

        /** @var Collection<int, XapiStatement> $attempts */
        $attempts = XapiStatement::query()
            ->forUser($user->getKey())
            ->forVerb(XapiRecorderService::VERB_ATTEMPTED)
            ->where('object_type', XapiRecorderService::OBJECT_QUIZ)
            ->whereIn('object_id', $itemIds)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get(['object_id', 'result', 'occurred_at']);

        if ($attempts->isEmpty()) {
            return null;
        }

        // Ne garder que la DERNIÈRE tentative par item (maîtrise actuelle).
        $latestPerItem = $attempts->groupBy('object_id')->map(fn (Collection $rows) => $rows->last());

        $percents = $latestPerItem
            ->map(fn (XapiStatement $s): ?float => isset($s->result['percent']) ? (float) $s->result['percent'] : null)
            ->filter(fn (?float $p): bool => $p !== null);

        if ($percents->isEmpty()) {
            return null;
        }

        return min(1.0, max(0.0, ((float) $percents->avg()) / 100));
    }
}
