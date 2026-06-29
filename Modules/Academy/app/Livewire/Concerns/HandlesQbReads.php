<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component QuestionBankManager — LECTURES owner-scopées :
 * propriétés computées (catégories, questions, étiquettes, statistiques, versions,
 * catégorie sélectionnée) et méthodes utilitaires de lecture (numéros de trous
 * ddwtos, pool de mots, libellé de type).
 *
 * Aucune écriture DB. Toutes les requêtes sont owner-scopées (sauf admin
 * academy.manage qui voit tout). Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuestionTag;
use Modules\Academy\Models\QuestionVersion;
use Modules\Academy\Services\QuestionStatsService;

trait HandlesQbReads
{
    // ─────────────────────────────────────────────────────────────────────────
    // Propriétés computées (listes fraîches, owner-scoped)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mes catégories (admin : toutes), triées par parent puis position, pour l'arbre
     * d'affichage ET de liste blanche (parent / catégorie sélectionnable).
     *
     * @return \Illuminate\Support\Collection<int, QuestionCategory>
     */
    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        $query = QuestionCategory::query()
            ->withCount(['questions', 'children'])
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * Catégories racines (parent_id null) pour l'affichage hiérarchique.
     *
     * @return \Illuminate\Support\Collection<int, QuestionCategory>
     */
    #[Computed]
    public function rootCategories(): \Illuminate\Support\Collection
    {
        return $this->categories->whereNull('parent_id')->values();
    }

    /**
     * Sous-catégories indexées par parent_id (affichage en arbre 1-2 niveaux).
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, QuestionCategory>>
     */
    #[Computed]
    public function childrenByParent(): \Illuminate\Support\Collection
    {
        return $this->categories->whereNotNull('parent_id')->groupBy('parent_id');
    }

    /**
     * Questions de la catégorie sélectionnée (owner-scoped via la résolution).
     *
     * @return \Illuminate\Support\Collection<int, Question>
     */
    #[Computed]
    public function questions(): \Illuminate\Support\Collection
    {
        if ($this->selectedCategoryId === null) {
            return collect();
        }

        $query = Question::where('category_id', $this->selectedCategoryId)
            ->with('tags')
            ->orderByDesc('id');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        // F17 (TAGS) : filtre optionnel par étiquette. Double garde anti-IDOR.
        if ($this->filterTagId !== null) {
            $tagId = (int) $this->filterTagId;
            $query->whereHas('tags', fn ($q) => $q->where('academy_question_tags.id', $tagId)
                ->where('academy_question_tags.owner_id', Auth::id()));
        }

        return $query->get();
    }

    /**
     * F17 (TAGS) - mes étiquettes (admin : toutes), pour le menu de filtre + la liste
     * d'aide à la saisie. Owner-scopé (sauf admin academy.manage).
     *
     * @return \Illuminate\Support\Collection<int, QuestionTag>
     */
    #[Computed]
    public function tags(): \Illuminate\Support\Collection
    {
        $query = QuestionTag::query()->orderBy('name');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * F17 (STATISTIQUES) - usages + indice de facilité pour les questions AFFICHÉES,
     * agrégés en UN lot (aucun N+1). Map id_question => stats.
     *
     * @return array<int, array{uses: int, correct: int, facility: int|null, has_data: bool}>
     */
    #[Computed]
    public function questionStats(): array
    {
        $ids = $this->questions->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return QuestionStatsService::forQuestions($ids);
    }

    /**
     * F17 (VERSIONS) - historique de la question dont le panneau est ouvert (lecture
     * seule), de la plus récente à la plus ancienne. Owner-scopé via resolveQuestion.
     *
     * @return \Illuminate\Support\Collection<int, QuestionVersion>
     */
    #[Computed]
    public function questionVersions(): \Illuminate\Support\Collection
    {
        if ($this->historyQuestionId === null) {
            return collect();
        }

        try {
            $question = $this->resolveQuestion((int) $this->historyQuestionId);
        } catch (\Throwable) {
            return collect();
        }

        return $question->versions()->get();
    }

    /**
     * La catégorie sélectionnée (ou null).
     */
    #[Computed]
    public function selectedCategory(): ?QuestionCategory
    {
        if ($this->selectedCategoryId === null) {
            return null;
        }

        try {
            return $this->resolveCategory((int) $this->selectedCategoryId);
        } catch (\Throwable) {
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes utilitaires de lecture (pas de computed Livewire)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GLISSER-TEXTE — numéros de trous (1-based, uniques, triés) détectés dans le texte
     * en cours d'édition.
     *
     * @return array<int, int>
     */
    public function ddwtosBlankNumbers(): array
    {
        preg_match_all('/\[\[(\d+)\]\]/', (string) $this->qDdwtosText, $m);
        $nums = array_values(array_unique(array_map('intval', $m[1] ?? [])));
        sort($nums);

        return $nums;
    }

    /**
     * GLISSER-TEXTE — pool de mots NON VIDES indexé par leur index D'ORIGINE.
     *
     * @return array<int, string>
     */
    public function ddwtosPool(): array
    {
        $pool = [];
        foreach ($this->qDdwtosWords as $wi => $w) {
            $wv = is_string($w) ? trim($w) : '';
            if ($wv !== '') {
                $pool[(int) $wi] = $wv;
            }
        }

        return $pool;
    }

    /** Libellé FR d'un type de question. */
    public function typeLabel(string $type): string
    {
        return match ($type) {
            'mcq'       => 'Choix multiple',
            'truefalse' => 'Vrai ou faux',
            'short'     => 'Réponse courte',
            'matching'  => 'Appariement',
            'ordering'  => 'Ordonnancement',
            'cloze'     => 'Texte à trous',
            'numerical' => 'Réponse numérique',
            'ddwtos'    => 'Glisser-déposer sur texte',
            'essay'     => 'Réponse rédigée (essai)',
            default     => $type,
        };
    }
}
