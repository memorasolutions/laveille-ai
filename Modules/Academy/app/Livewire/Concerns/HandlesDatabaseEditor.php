<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — éditeur répéteur de champs de
 * schéma pour la base de données (Database, F20) : gabarit vierge, ajout/retrait
 * à la création et à l'édition, chargement/annulation/enregistrement du tampon.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR). La résolution anti-IDOR de l'item
 * passe par resolveItemFor() avant toute écriture. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

trait HandlesDatabaseEditor
{
    // ─────────────────────────────────────────────────────────────────────────────
    // F20 - BASE DE DONNÉES : répéteur de CHAMPS (schéma). Comme la rétroaction, un
    // répéteur dynamique ne se prête pas au $event.target inline : on passe par un
    // tampon Livewire (newItem.{lesson}.database_fields à la création,
    // editDatabase.{item} à l'édition) + actions dédiées. La GESTION DU SCHÉMA est
    // réservée au gérant : chaque action ré-autorise manageStructure (anti-IDOR via
    // resolveItemFor).
    // ─────────────────────────────────────────────────────────────────────────────

    /** Gabarit d'un champ vierge (répéteur de schéma). */
    private function blankDatabaseField(): array
    {
        return ['label' => '', 'type' => 'text', 'required' => false, 'options' => ''];
    }

    /** NOUVEL item database : ajoute un champ vierge au schéma. */
    public function addNewDatabaseField(int $lessonId): void
    {
        // Défense en profondeur : même une mutation du tampon exige le droit de gérer.
        $this->authorize('manageStructure', $this->resolveCourse());
        $this->newItem[$lessonId]['database_fields'][] = $this->blankDatabaseField();
    }

    /** NOUVEL item database : retire le champ d'index donné (réindexé). */
    public function removeNewDatabaseField(int $lessonId, int $index): void
    {
        $this->authorize('manageStructure', $this->resolveCourse());
        if (isset($this->newItem[$lessonId]['database_fields'][$index])) {
            unset($this->newItem[$lessonId]['database_fields'][$index]);
            $this->newItem[$lessonId]['database_fields'] = array_values($this->newItem[$lessonId]['database_fields']);
        }
    }

    /**
     * ÉDITION : charge le tampon d'édition du SCHÉMA d'un item database depuis ses
     * champs. Anti-IDOR : l'item doit appartenir à CE cours. Réservé au gérant
     * (manageStructure). Les options « select » sont converties en chaîne multiligne
     * pour l'édition.
     */
    public function loadDatabaseEditor(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $item = $this->resolveItemFor($course, $itemId);

        if ($item->type !== 'database') {
            return;
        }

        $fields = [];
        foreach (\Modules\Academy\Services\DatabaseService::fields($item) as $f) {
            $fields[] = [
                'id'       => $f->id,
                'label'    => $f->label,
                'type'     => $f->type,
                'required' => (bool) $f->required,
                'options'  => is_array($f->options) ? implode("\n", $f->options) : '',
            ];
        }

        $this->editDatabase[$itemId] = [
            'title'             => $item->title,
            'intro'             => \Modules\Academy\Services\DatabaseService::intro($item),
            'allow_student_add' => \Modules\Academy\Services\DatabaseService::allowsStudentAdd($item),
            'require_approval'  => \Modules\Academy\Services\DatabaseService::requiresApproval($item),
            'completion'        => \Modules\Academy\Services\ActivityCompletionService::criterionFor($item),
            'estimated_minutes' => $item->estimated_minutes,
            'fields'            => $fields,
        ];
    }

    /** Abandonne l'édition en cours du schéma (vide le tampon). */
    public function cancelDatabaseEditor(int $itemId): void
    {
        unset($this->editDatabase[$itemId]);
    }

    /** ÉDITION : ajoute un champ vierge au schéma en cours d'édition. */
    public function addDatabaseField(int $itemId): void
    {
        $this->authorize('manageStructure', $this->resolveCourse());
        $this->editDatabase[$itemId]['fields'][] = $this->blankDatabaseField();
    }

    /** ÉDITION : retire le champ d'index donné du schéma (réindexé). */
    public function removeDatabaseField(int $itemId, int $index): void
    {
        $this->authorize('manageStructure', $this->resolveCourse());
        if (isset($this->editDatabase[$itemId]['fields'][$index])) {
            unset($this->editDatabase[$itemId]['fields'][$index]);
            $this->editDatabase[$itemId]['fields'] = array_values($this->editDatabase[$itemId]['fields']);
        }
    }

    /**
     * ÉDITION : enregistre un item database depuis son tampon (payload + SCHÉMA).
     * Mêmes gardes que updateItem (resolveCourse -> manageStructure ->
     * resolveItemFor anti-IDOR -> validateItem -> buildItemPayload), puis
     * synchronisation des champs via le service.
     */
    public function saveDatabase(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $item = $this->resolveItemFor($course, $itemId);

        if ($item->type !== 'database') {
            abort(404);
        }

        $buffer  = $this->editDatabase[$itemId] ?? [];
        $minutes = $buffer['estimated_minutes'] ?? null;

        $input = [
            'type'              => 'database',
            'title'             => (string) ($buffer['title'] ?? $item->title),
            'estimated_minutes' => ($minutes === '' || $minutes === null) ? null : (int) $minutes,
            'database_intro'    => $buffer['intro'] ?? null,
            'allow_student_add' => $buffer['allow_student_add'] ?? null,
            'require_approval'  => $buffer['require_approval'] ?? null,
            'completion'        => $buffer['completion'] ?? null,
        ];

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload('database', $input);

        $item->update([
            'type'              => 'database',
            'title'             => $data['title'],
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
        ]);

        // SCHÉMA : synchronise les champs (création / mise à jour / soft-suppression).
        \Modules\Academy\Services\DatabaseService::syncFields($item, $buffer['fields'] ?? []);

        unset($this->editDatabase[$itemId]);
        $this->flashSaved('Base de données mise à jour.');
    }
}
