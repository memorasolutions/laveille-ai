<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — feedback global par tranche de
 * score (« grade boundaries » Moodle, V1-a) : chargement, ajout/retrait de
 * borne et enregistrement normalisé via QuizFeedbackService.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR). La résolution anti-IDOR de l'item
 * passe par $this->resolveItemFor() avant toute écriture. Aucun comportement
 * modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

trait HandlesOverallFeedback
{
    // ─────────────────────────────────────────────────────────────────────────────
    // V1-a : FEEDBACK GLOBAL PAR TRANCHE DE SCORE (item quiz) - gâté manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → resolveItemFor()
    // (anti-IDOR : l'item doit appartenir à CE cours) → normalisation/validation des
    // bornes (QuizFeedbackService) → écriture dans payload['overall_feedback'].
    // Une liste vide efface la clé (rétrocompat : pas de clé parasite).
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Charge dans $overallFeedback[item] les bornes existantes d'un item (édition).
     * Re-résout l'item scopé à CE cours (anti-IDOR). Ajoute une ligne vide si aucune,
     * pour que l'UI propose toujours un point de départ.
     */
    public function loadOverallFeedback(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $rows = [];
        foreach ((array) ($item->payload['overall_feedback'] ?? []) as $row) {
            if (is_array($row) && isset($row['message'])) {
                $rows[] = [
                    'min_percent' => (int) ($row['min_percent'] ?? 0),
                    'message'     => (string) $row['message'],
                ];
            }
        }

        if ($rows === []) {
            $rows[] = ['min_percent' => 80, 'message' => ''];
        }

        $this->overallFeedback[$itemId] = $rows;
    }

    public function addOverallBoundary(int $itemId): void
    {
        if (! isset($this->overallFeedback[$itemId])) {
            $this->overallFeedback[$itemId] = [];
        }

        if (count($this->overallFeedback[$itemId]) >= \Modules\Academy\Services\QuizFeedbackService::MAX_BOUNDARIES) {
            return; // garde-fou : pas plus que le maximum autorisé.
        }

        $this->overallFeedback[$itemId][] = ['min_percent' => 0, 'message' => ''];
    }

    public function removeOverallBoundary(int $itemId, int $index): void
    {
        if (! isset($this->overallFeedback[$itemId][$index])) {
            return;
        }

        unset($this->overallFeedback[$itemId][$index]);
        $this->overallFeedback[$itemId] = array_values($this->overallFeedback[$itemId]);
    }

    /**
     * Enregistre le feedback global d'un item quiz. La liste est NORMALISÉE/validée
     * par QuizFeedbackService (seuils 0..100, messages bornés, dédoublonnage, tri DESC,
     * max bornes). Une liste vide retire la clé (rétrocompat).
     */
    public function saveOverallFeedback(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $clean = \Modules\Academy\Services\QuizFeedbackService::normalizeBoundaries(
            $this->overallFeedback[$itemId] ?? []
        );

        $payload = is_array($item->payload) ? $item->payload : [];
        if ($clean === []) {
            unset($payload['overall_feedback']);
        } else {
            $payload['overall_feedback'] = $clean;
        }

        $item->update(['payload' => $payload]);

        // Reflète l'état normalisé dans l'UI (tri/dédoublonnage visibles immédiatement).
        $this->overallFeedback[$itemId] = $clean !== []
            ? $clean
            : [['min_percent' => 80, 'message' => '']];

        $this->flashSaved('Rétroaction globale du quiz enregistrée.');
    }
}
