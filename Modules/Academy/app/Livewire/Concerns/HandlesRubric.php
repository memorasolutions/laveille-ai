<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseAssignments — Construction et gestion de la
 * grille d'évaluation (rubric V2-a) d'un devoir : critères (CRUD) + niveaux (CRUD),
 * ouverture/fermeture du panneau, confirmations inline à 2 temps.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse()
 * et re-autorise via $this->authorize('manageStructure', ...). Les critères et
 * niveaux sont re-résolus et scopés au cours (via l'arbre assignment→course_id,
 * criterion→assignment→course_id, level→criterion→assignment→course_id) avant toute
 * écriture (anti-IDOR). Aucun comportement modifié par rapport au composant d'origine.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;

trait HandlesRubric
{
    // ─────────────────────────────────────────────────────────────────────────
    // V2-a : CONSTRUIRE la grille d'évaluation (rubric) - gâté manageStructure
    // ─────────────────────────────────────────────────────────────────────────

    /** Ouvre le panneau « Grille » d'un devoir de CE cours (anti-IDOR). */
    public function openRubric(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);

        $this->rubricAssignment = $assignment->id;
        $this->resetRubricForms();
    }

    public function closeRubric(): void
    {
        $this->rubricAssignment = null;
        $this->resetRubricForms();
    }

    /** Réinitialise tous les sous-formulaires de la grille (sans fermer le panneau). */
    private function resetRubricForms(): void
    {
        $this->reset(
            'newCriterion', 'editingCriterion', 'criterionDescription',
            'addingLevelTo', 'editingLevel', 'levelDescription', 'levelPoints',
            'confirmingCriterionRemoval', 'confirmingLevelRemoval',
        );
        $this->resetErrorBag(['newCriterion', 'criterionDescription', 'levelDescription', 'levelPoints']);
    }

    /** Ajoute un critère au devoir dont le panneau est ouvert (anti-IDOR). */
    public function addCriterion(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->rubricAssignment === null) {
            return;
        }
        $assignment = $this->resolveAssignmentFor($course, (int) $this->rubricAssignment);

        $this->validate(
            ['newCriterion' => 'required|string|max:500'],
            [],
            ['newCriterion' => 'critère'],
        );

        $position = (int) RubricCriterion::where('assignment_id', $assignment->id)->max('position') + 1;

        RubricCriterion::create([
            'assignment_id' => $assignment->id,
            'description'   => trim($this->newCriterion),
            'position'      => $position,
        ]);

        $this->newCriterion = '';
        $this->resetErrorBag('newCriterion');
        $this->flashSaved('Critère ajouté.');
    }

    /** Charge un critère de CE cours en édition (anti-IDOR). */
    public function editCriterion(int $criterionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $criterion = $this->resolveCriterionFor($course, $criterionId);

        $this->editingCriterion     = $criterion->id;
        $this->criterionDescription = $criterion->description;
        $this->resetErrorBag('criterionDescription');
    }

    public function saveCriterion(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingCriterion === null) {
            return;
        }
        $criterion = $this->resolveCriterionFor($course, (int) $this->editingCriterion);

        $this->validate(
            ['criterionDescription' => 'required|string|max:500'],
            [],
            ['criterionDescription' => 'critère'],
        );

        $criterion->update(['description' => trim($this->criterionDescription)]);

        $this->reset('editingCriterion', 'criterionDescription');
        $this->flashSaved('Critère modifié.');
    }

    public function cancelCriterionEdit(): void
    {
        $this->reset('editingCriterion', 'criterionDescription');
        $this->resetErrorBag('criterionDescription');
    }

    public function confirmCriterionRemoval(int $criterionId): void
    {
        $this->confirmingCriterionRemoval = $criterionId;
    }

    public function cancelCriterionRemoval(): void
    {
        $this->confirmingCriterionRemoval = null;
    }

    /** Supprime un critère de CE cours (niveaux en cascade), anti-IDOR. */
    public function deleteCriterion(int $criterionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $criterion = $this->resolveCriterionFor($course, $criterionId);
        $criterion->delete();

        $this->confirmingCriterionRemoval = null;
        if ($this->editingCriterion === $criterionId) {
            $this->reset('editingCriterion', 'criterionDescription');
        }
        if ($this->addingLevelTo === $criterionId) {
            $this->reset('addingLevelTo', 'levelDescription', 'levelPoints');
        }
        $this->flashSaved('Critère supprimé.');
    }

    /** Ouvre le formulaire d'ajout d'un niveau à un critère de CE cours (anti-IDOR). */
    public function startAddLevel(int $criterionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $criterion = $this->resolveCriterionFor($course, $criterionId);

        $this->addingLevelTo    = $criterion->id;
        $this->editingLevel     = null;
        $this->levelDescription = '';
        $this->levelPoints      = '';
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    public function cancelAddLevel(): void
    {
        $this->reset('addingLevelTo', 'levelDescription', 'levelPoints');
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    /** Ajoute un niveau au critère ciblé (re-résolu scopé au cours, anti-IDOR). */
    public function addLevel(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->addingLevelTo === null) {
            return;
        }
        $criterion = $this->resolveCriterionFor($course, (int) $this->addingLevelTo);

        $data = $this->validate([
            'levelDescription' => 'required|string|max:500',
            'levelPoints'      => 'required|integer|min:0|max:100000',
        ], [], [
            'levelDescription' => 'libellé du niveau',
            'levelPoints'      => 'points',
        ]);

        $position = (int) RubricLevel::where('criterion_id', $criterion->id)->max('position') + 1;

        RubricLevel::create([
            'criterion_id' => $criterion->id,
            'description'  => trim($data['levelDescription']),
            'points'       => (int) $data['levelPoints'],
            'position'     => $position,
        ]);

        $this->reset('addingLevelTo', 'levelDescription', 'levelPoints');
        $this->flashSaved('Niveau ajouté.');
    }

    /** Charge un niveau de CE cours en édition (anti-IDOR). */
    public function editLevel(int $levelId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $level = $this->resolveLevelFor($course, $levelId);

        $this->editingLevel     = $level->id;
        $this->addingLevelTo    = null;
        $this->levelDescription = $level->description;
        $this->levelPoints      = (string) $level->points;
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    public function saveLevel(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingLevel === null) {
            return;
        }
        $level = $this->resolveLevelFor($course, (int) $this->editingLevel);

        $data = $this->validate([
            'levelDescription' => 'required|string|max:500',
            'levelPoints'      => 'required|integer|min:0|max:100000',
        ], [], [
            'levelDescription' => 'libellé du niveau',
            'levelPoints'      => 'points',
        ]);

        $level->update([
            'description' => trim($data['levelDescription']),
            'points'      => (int) $data['levelPoints'],
        ]);

        $this->reset('editingLevel', 'levelDescription', 'levelPoints');
        $this->flashSaved('Niveau modifié.');
    }

    public function cancelLevelEdit(): void
    {
        $this->reset('editingLevel', 'levelDescription', 'levelPoints');
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    public function confirmLevelRemoval(int $levelId): void
    {
        $this->confirmingLevelRemoval = $levelId;
    }

    public function cancelLevelRemoval(): void
    {
        $this->confirmingLevelRemoval = null;
    }

    /** Supprime un niveau de CE cours (re-résolu scopé), anti-IDOR. */
    public function deleteLevel(int $levelId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $level = $this->resolveLevelFor($course, $levelId);
        $level->delete();

        $this->confirmingLevelRemoval = null;
        if ($this->editingLevel === $levelId) {
            $this->reset('editingLevel', 'levelDescription', 'levelPoints');
        }
        $this->flashSaved('Niveau supprimé.');
    }
}
