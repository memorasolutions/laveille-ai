<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — éditeur répéteur de critères
 * d'évaluation pour l'atelier (Workshop, F21) : gabarit vierge, ajout/retrait
 * à la création et à l'édition, chargement/annulation/enregistrement du tampon.
 * La phase en cours est PRÉSERVÉE lors de l'enregistrement (elle se pilote depuis
 * le lecteur, pas l'éditeur).
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR). La résolution anti-IDOR de l'item
 * passe par resolveItemFor() avant toute écriture. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

trait HandlesWorkshopEditor
{
    // ─────────────────────────────────────────────────────────────────────────────
    // F21 - ATELIER (workshop) : répéteur de critères (NOUVEL item + ÉDITION). Comme
    // la base de données, la grille passe par un tampon Livewire (newItem.{lesson}.
    // workshop_criteria à la création, editWorkshop.{item} à l'édition) + actions
    // dédiées.
    // ─────────────────────────────────────────────────────────────────────────────

    /** Gabarit d'un critère vierge (répéteur de grille). */
    private function blankWorkshopCriterion(): array
    {
        return ['label' => '', 'description' => '', 'max_score' => 10, 'weight' => 1];
    }

    /** NOUVEL item workshop : ajoute un critère vierge à la grille. */
    public function addNewWorkshopCriterion(int $lessonId): void
    {
        $this->newItem[$lessonId]['workshop_criteria'][] = $this->blankWorkshopCriterion();
    }

    /** NOUVEL item workshop : retire le critère d'index donné (réindexé). */
    public function removeNewWorkshopCriterion(int $lessonId, int $index): void
    {
        if (isset($this->newItem[$lessonId]['workshop_criteria'][$index])) {
            unset($this->newItem[$lessonId]['workshop_criteria'][$index]);
            $this->newItem[$lessonId]['workshop_criteria'] = array_values($this->newItem[$lessonId]['workshop_criteria']);
        }
    }

    /**
     * ÉDITION : charge le tampon d'édition d'un item workshop depuis ses critères
     * et réglages. Anti-IDOR : l'item doit appartenir à CE cours. Réservé au gérant
     * (manageStructure).
     */
    public function loadWorkshopEditor(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $item = $this->resolveItemFor($course, $itemId);

        if ($item->type !== 'workshop') {
            return;
        }

        $criteria = [];
        foreach (\Modules\Academy\Services\WorkshopService::criteria($item) as $c) {
            $criteria[] = [
                'id'          => $c->id,
                'label'       => $c->label,
                'description' => $c->description ?? '',
                'max_score'   => $c->max_score,
                'weight'      => $c->weight,
            ];
        }

        $this->editWorkshop[$itemId] = [
            'title'               => $item->title,
            'intro'               => \Modules\Academy\Services\WorkshopService::intro($item),
            'reviews_per_student' => \Modules\Academy\Services\WorkshopService::reviewsPerStudent($item),
            'anonymous'           => \Modules\Academy\Services\WorkshopService::isAnonymous($item),
            'estimated_minutes'   => $item->estimated_minutes,
            'criteria'            => $criteria,
        ];
    }

    /** Abandonne l'édition en cours de l'atelier (vide le tampon). */
    public function cancelWorkshopEditor(int $itemId): void
    {
        unset($this->editWorkshop[$itemId]);
    }

    /** ÉDITION : ajoute un critère vierge à la grille en cours d'édition. */
    public function addWorkshopCriterion(int $itemId): void
    {
        $this->editWorkshop[$itemId]['criteria'][] = $this->blankWorkshopCriterion();
    }

    /** ÉDITION : retire le critère d'index donné de la grille (réindexé). */
    public function removeWorkshopCriterion(int $itemId, int $index): void
    {
        if (isset($this->editWorkshop[$itemId]['criteria'][$index])) {
            unset($this->editWorkshop[$itemId]['criteria'][$index]);
            $this->editWorkshop[$itemId]['criteria'] = array_values($this->editWorkshop[$itemId]['criteria']);
        }
    }

    /**
     * ÉDITION : enregistre un item workshop depuis son tampon (payload + GRILLE).
     * Mêmes gardes que updateItem (resolveCourse -> manageStructure ->
     * resolveItemFor anti-IDOR -> validateItem -> buildItemPayload), puis
     * synchronisation des critères via le service.
     * La PHASE en cours est PRÉSERVÉE (elle se pilote depuis le lecteur, pas
     * l'éditeur).
     */
    public function saveWorkshop(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $item = $this->resolveItemFor($course, $itemId);

        if ($item->type !== 'workshop') {
            abort(404);
        }

        $buffer  = $this->editWorkshop[$itemId] ?? [];
        $minutes = $buffer['estimated_minutes'] ?? null;

        $input = [
            'type'                => 'workshop',
            'title'               => (string) ($buffer['title'] ?? $item->title),
            'estimated_minutes'   => ($minutes === '' || $minutes === null) ? null : (int) $minutes,
            'workshop_intro'      => $buffer['intro'] ?? null,
            'reviews_per_student' => $buffer['reviews_per_student'] ?? null,
            'workshop_anonymous'  => $buffer['anonymous'] ?? null,
            // PRÉSERVE la phase actuelle (l'éditeur ne la change pas).
            'workshop_phase'      => \Modules\Academy\Services\WorkshopService::phase($item),
        ];

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload('workshop', $input);

        $item->update([
            'type'              => 'workshop',
            'title'             => $data['title'],
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
        ]);

        // GRILLE : synchronise les critères (création / mise à jour / soft-suppression).
        \Modules\Academy\Services\WorkshopService::syncCriteria($item, $buffer['criteria'] ?? []);

        unset($this->editWorkshop[$itemId]);
        $this->flashSaved('Atelier mis à jour.');
    }
}
