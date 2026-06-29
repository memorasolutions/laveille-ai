<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component QuestionBankManager — CRUD des catégories de la
 * banque de questions : création (owner-scoped), renommage inline, suppression
 * gardée, sélection, confirmations inline à 2 temps.
 *
 * SÉCURITÉ : chaque mutation re-résout la catégorie via $this->resolveCategory()
 * (anti-IDOR), exactement comme dans le composant d'origine. Le parent à la
 * création est validé contre les catégories de l'utilisateur courant. La
 * suppression est bloquée si la catégorie contient des questions ou des
 * sous-catégories (aucune cascade silencieuse). Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Academy\Models\QuestionCategory;

trait HandlesQbCategories
{
    // ─────────────────────────────────────────────────────────────────────────
    // CATÉGORIES
    // ─────────────────────────────────────────────────────────────────────────

    public function createCategory(): void
    {
        $this->normalizeNullableInt('newCategoryParentId');

        $data = $this->validate([
            'newCategoryName'     => 'required|string|max:160',
            'newCategoryParentId' => 'nullable|integer',
        ]);

        // Anti-IDOR : le parent doit appartenir à MES catégories (re-résolu serveur).
        $parentId = null;
        if (! empty($data['newCategoryParentId'])) {
            $parent   = $this->resolveCategory((int) $data['newCategoryParentId']);
            $parentId = $parent->id;
        }

        $position = (int) QuestionCategory::where('owner_id', Auth::id())
            ->where('parent_id', $parentId)
            ->max('position') + 1;

        QuestionCategory::create([
            'owner_id'  => Auth::id(), // FORCÉ = auth (jamais du navigateur).
            'parent_id' => $parentId,
            'name'      => trim($data['newCategoryName']),
            'position'  => $position,
        ]);

        $this->reset('newCategoryName', 'newCategoryParentId');
        session()->flash('academy_bank_status', 'Catégorie créée.');
    }

    public function startRenameCategory(int $categoryId): void
    {
        $category = $this->resolveCategory($categoryId);

        $this->renamingCategory   = $category->id;
        $this->renameCategoryName = $category->name;
        $this->resetErrorBag('renameCategoryName');
    }

    public function cancelRenameCategory(): void
    {
        $this->renamingCategory = null;
        $this->reset('renameCategoryName');
        $this->resetErrorBag('renameCategoryName');
    }

    public function renameCategory(): void
    {
        if ($this->renamingCategory === null) {
            return;
        }

        $category = $this->resolveCategory((int) $this->renamingCategory);

        $data = $this->validate([
            'renameCategoryName' => 'required|string|max:160',
        ]);

        $category->update(['name' => trim($data['renameCategoryName'])]);

        $this->renamingCategory = null;
        $this->reset('renameCategoryName');
        session()->flash('academy_bank_status', 'Catégorie renommée.');
    }

    /**
     * Supprime une catégorie. BLOQUÉ si elle contient des questions OU des
     * sous-catégories (choix le plus sûr : aucune perte silencieuse en cascade).
     */
    public function deleteCategory(int $categoryId): void
    {
        $category = $this->resolveCategory($categoryId);

        $hasQuestions = $category->questions()->exists();
        $hasChildren  = $category->children()->exists();

        if ($hasQuestions || $hasChildren) {
            $this->confirmingCategoryDeletion = null;
            session()->flash(
                'academy_bank_error',
                'Impossible de supprimer une catégorie qui contient des questions ou des sous-catégories. Videz-la d\'abord.'
            );

            return;
        }

        $category->delete();

        if ($this->selectedCategoryId === $categoryId) {
            $this->selectedCategoryId = null;
            $this->resetQuestionForm();
        }
        $this->confirmingCategoryDeletion = null;
        session()->flash('academy_bank_status', 'Catégorie supprimée.');
    }

    /** Sélectionne une catégorie (affiche ses questions + le formulaire). */
    public function selectCategory(int $categoryId): void
    {
        $category = $this->resolveCategory($categoryId);

        $this->selectedCategoryId = $category->id;
        $this->filterTagId        = null; // nouvelle catégorie → on repart sans filtre.
        $this->historyQuestionId  = null;
        $this->resetQuestionForm();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps — catégories
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmCategoryDeletion(int $categoryId): void
    {
        $this->confirmingCategoryDeletion = $categoryId;
    }

    public function cancelCategoryDeletion(): void
    {
        $this->confirmingCategoryDeletion = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utilitaire interne
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Normalise une propriété ?int issue du DOM : '' / 0 / null → null (anti-TypeError
     * sur strict_types, et cohérence « pas de parent »).
     */
    private function normalizeNullableInt(string $prop): void
    {
        $value = $this->{$prop};
        $this->{$prop} = ($value === '' || $value === null || (int) $value === 0)
            ? null
            : (int) $value;
    }
}
