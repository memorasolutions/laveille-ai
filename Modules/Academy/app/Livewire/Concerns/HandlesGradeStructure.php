<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseAssignments — Carnet de notes pondéré
 * (V2-b) : CRUD catégories (owner-scopé), affectation items↔catégories, poids
 * par item, barème de lettres, export CSV, et bascule du carnet de notes.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse()
 * et re-autorise. Catégories, items et catégories cibles sont re-résolus et
 * scopés au cours (anti-IDOR) avant toute écriture. La méthode d'agrégation
 * est validée contre une liste blanche serveur. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Validation\Rule;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\GradeItem;
use Modules\Academy\Services\GradebookService;

trait HandlesGradeStructure
{
    // ─────────────────────────────────────────────────────────────────────────
    // CARNET DE NOTES (gradebook) — bascule - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleGradebook(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $this->showGradebook = ! $this->showGradebook;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // V2-b : CARNET PONDÉRÉ — catégories, affectation des items, lettres
    // (configuration gâtée manageStructure ; export gâté manageEnrollments)
    // ─────────────────────────────────────────────────────────────────────────

    /** Ouvre/ferme le panneau de pondération et (ré)hydrate les maps depuis la BD. */
    public function toggleGradeStructure(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->showGradeStructure = ! $this->showGradeStructure;

        if ($this->showGradeStructure) {
            $this->loadGradeStructure();
        }
    }

    /** Recharge les affectations item↔catégorie + le barème de lettres depuis la BD. */
    private function loadGradeStructure(): void
    {
        $course = $this->resolveCourse();

        $this->itemCategoryMap = [];
        $this->itemWeightMap   = [];
        foreach (GradebookService::gradableItems($course) as $item) {
            $key = $item['type'].'_'.$item['id'];
            $this->itemCategoryMap[$key] = $item['category_id'] !== null ? (string) $item['category_id'] : '';
            $iw = (string) $item['weight']; // ne trimmer les zéros QUE pour un décimal (sinon « 60 » -> « 6 »)
            $this->itemWeightMap[$key]   = (str_contains($iw, '.') ? rtrim(rtrim($iw, '0'), '.') : $iw) ?: '1';
        }

        $this->letterBands = [];
        foreach (GradebookService::letterSchemeFor($course) as $band) {
            $this->letterBands[] = [
                'letter' => (string) $band['letter'],
                'min'    => rtrim(rtrim((string) $band['min'], '0'), '.') ?: '0',
            ];
        }
    }

    /** Ajoute une catégorie de notes (gâté manageStructure, scopé au cours). */
    public function addCategory(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $data = $this->validate([
            'newCategoryName'   => 'required|string|max:120',
            'newCategoryWeight' => 'required|numeric|min:0|max:100',
            // F14 : méthode d'agrégation contrainte par la LISTE BLANCHE (serveur).
            'newCategoryMethod' => ['required', Rule::in(GradeCategory::AGGREGATION_METHODS)],
        ], [], [
            'newCategoryName'   => 'nom de la catégorie',
            'newCategoryWeight' => 'poids',
            'newCategoryMethod' => 'méthode d\'agrégation',
        ]);

        $position = (int) GradeCategory::where('course_id', $course->id)->max('position') + 1;

        GradeCategory::create([
            'course_id'          => $course->id,
            'name'               => trim($data['newCategoryName']),
            'weight'             => (float) $data['newCategoryWeight'],
            'aggregation_method' => $data['newCategoryMethod'],
            'position'           => $position,
        ]);

        $this->reset('newCategoryName', 'newCategoryWeight');
        $this->newCategoryMethod = GradeCategory::AGGREGATION_WEIGHTED_MEAN;
        $this->loadGradeStructure();
        $this->flashSaved('Catégorie ajoutée.');
    }

    /** Charge une catégorie de CE cours en édition (anti-IDOR). */
    public function editCategory(int $categoryId): void
    {
        $course   = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $category = $this->resolveCategoryFor($course, $categoryId);

        $this->editingCategory    = $category->id;
        $this->editCategoryName   = $category->name;
        $cw = (string) $category->weight; // ne trimmer les zéros QUE pour un décimal (sinon « 60 » -> « 6 »)
        $this->editCategoryWeight = (str_contains($cw, '.') ? rtrim(rtrim($cw, '0'), '.') : $cw) ?: '0';
        $this->editCategoryMethod = $category->effectiveAggregationMethod();
        $this->resetErrorBag(['editCategoryName', 'editCategoryWeight', 'editCategoryMethod']);
    }

    public function saveCategory(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingCategory === null) {
            return;
        }
        $category = $this->resolveCategoryFor($course, (int) $this->editingCategory);

        $data = $this->validate([
            'editCategoryName'   => 'required|string|max:120',
            'editCategoryWeight' => 'required|numeric|min:0|max:100',
            'editCategoryMethod' => ['required', Rule::in(GradeCategory::AGGREGATION_METHODS)],
        ], [], [
            'editCategoryName'   => 'nom de la catégorie',
            'editCategoryWeight' => 'poids',
            'editCategoryMethod' => 'méthode d\'agrégation',
        ]);

        $category->update([
            'name'               => trim($data['editCategoryName']),
            'weight'             => (float) $data['editCategoryWeight'],
            'aggregation_method' => $data['editCategoryMethod'],
        ]);

        $this->reset('editingCategory', 'editCategoryName', 'editCategoryWeight');
        $this->editCategoryMethod = GradeCategory::AGGREGATION_WEIGHTED_MEAN;
        $this->flashSaved('Catégorie modifiée.');
    }

    public function cancelCategoryEdit(): void
    {
        $this->reset('editingCategory', 'editCategoryName', 'editCategoryWeight');
        $this->editCategoryMethod = GradeCategory::AGGREGATION_WEIGHTED_MEAN;
        $this->resetErrorBag(['editCategoryName', 'editCategoryWeight', 'editCategoryMethod']);
    }

    public function confirmCategoryRemoval(int $categoryId): void
    {
        $this->confirmingCategoryRemoval = $categoryId;
    }

    public function cancelCategoryRemoval(): void
    {
        $this->confirmingCategoryRemoval = null;
    }

    /**
     * Supprime une catégorie de CE cours (anti-IDOR). Les items affectés deviennent
     * « non classés » (grade_category_id → null via FK set null), JAMAIS supprimés.
     */
    public function deleteCategory(int $categoryId): void
    {
        $course   = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $category = $this->resolveCategoryFor($course, $categoryId);

        $category->delete();

        $this->confirmingCategoryRemoval = null;
        if ($this->editingCategory === $categoryId) {
            $this->reset('editingCategory', 'editCategoryName', 'editCategoryWeight');
        }
        $this->loadGradeStructure();
        $this->flashSaved('Catégorie supprimée (les items sont devenus non classés).');
    }

    /**
     * Enregistre l'affectation de CHAQUE item à une catégorie + son poids. Chaque
     * item et chaque catégorie sont RE-RÉSOLUS scopés à CE cours (anti-IDOR) ; une
     * valeur étrangère est ignorée. Catégorie vide = item retiré de la pondération
     * (ligne supprimée, jamais d'autre donnée touchée).
     */
    public function saveItemAssignments(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        foreach ($this->itemCategoryMap as $key => $catRaw) {
            [$type, $idStr] = array_pad(explode('_', (string) $key, 2), 2, null);
            if (! in_array($type, GradeItem::TYPES, true) || $idStr === null || ! ctype_digit((string) $idStr)) {
                continue;
            }
            $itemId = (int) $idStr;

            // Anti-IDOR : l'item doit appartenir à CE cours.
            if (! $this->itemBelongsToCourse($course, $type, $itemId)) {
                continue;
            }

            $catId = trim((string) $catRaw) === '' ? null : (int) $catRaw;
            // Anti-IDOR : la catégorie (si fournie) doit appartenir à CE cours.
            if ($catId !== null && ! GradeCategory::where('id', $catId)->where('course_id', $course->id)->exists()) {
                $catId = null;
            }

            $weightRaw = $this->itemWeightMap[$key] ?? '1';
            $weight    = is_numeric($weightRaw) ? max(0.0, (float) $weightRaw) : 1.0;

            if ($catId === null) {
                GradeItem::where('course_id', $course->id)
                    ->where('item_type', $type)
                    ->where('item_id', $itemId)
                    ->delete();

                continue;
            }

            GradeItem::updateOrCreate(
                ['course_id' => $course->id, 'item_type' => $type, 'item_id' => $itemId],
                ['grade_category_id' => $catId, 'weight' => $weight],
            );
        }

        $this->loadGradeStructure();
        $this->flashSaved('Affectations enregistrées.');
    }

    /** Ajoute une bande au barème de lettres en cours d'édition (non persistée). */
    public function addLetterBand(): void
    {
        $this->letterBands[] = ['letter' => '', 'min' => '0'];
    }

    /** Retire une bande du barème en cours d'édition (non persistée). */
    public function removeLetterBand(int $index): void
    {
        if (array_key_exists($index, $this->letterBands)) {
            unset($this->letterBands[$index]);
            $this->letterBands = array_values($this->letterBands);
        }
    }

    /**
     * Enregistre le barème de lettres du cours (JSON courses.grade_letter_scheme).
     * Barème vide/illisible → null = défaut raisonnable (rétrocompat). Gâté
     * manageStructure, cours re-résolu (anti-IDOR).
     */
    public function saveLetterScheme(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $clean = GradebookService::sanitizeScheme($this->letterBands);

        $course->update(['grade_letter_scheme' => $clean === [] ? null : $clean]);

        $this->loadGradeStructure();
        $this->flashSaved('Barème de lettres enregistré.');
    }

    /**
     * EXPORT CSV du carnet (inscrits × items + note finale + lettre). Génération
     * SERVEUR (StreamedResponse), BOM UTF-8 + séparateur « ; » (Excel FR). Gâté
     * manageEnrollments, cours re-résolu et données scopées au cours (anti-IDOR).
     */
    public function exportGradebookCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $csv      = GradebookService::buildCsv($course);
        $filename = 'carnet-notes-'.$course->slug.'-'.now('America/Toronto')->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
