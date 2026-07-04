<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gestion des CATÉGORIES DE COURS (taxonomie simple, parité Moodle - Vague 4).
 * Réservée à academy.manage : la taxonomie est PARTAGÉE site-wide, un formateur
 * choisit une catégorie existante pour SES cours (voir CourseEditor) mais n'en
 * crée/renomme/supprime aucune - évite les doublons/conflits entre formateurs
 * (décision de conception, voir rapport de livraison).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - Drapeau academy.course_categories_enabled re-vérifié à l'entrée ET à
 *    chaque mutation (une action Livewire est un endpoint public : elle
 *    contourne le middleware de la route).
 *  - Chaque mutation ré-vérifie academy.manage (jamais de confiance au seul
 *    montage - un rôle peut changer en cours de session).
 *  - Suppression : les cours de la catégorie supprimée ne sont PAS supprimés
 *    (FK nullOnDelete) - ils redeviennent simplement « sans catégorie ».
 *  - Jamais de popup native (confirm/alert) : confirmation inline à 2 temps.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\CourseCategory;

class CourseCategoryManager extends Component
{
    // ── Formulaire de création/édition ──────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $color = null;

    public ?string $icon = null;

    /** Id en attente de confirmation de suppression (modale inline, jamais confirm() natif). */
    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);
    }

    /** Drapeau maître de la fonctionnalité (défaut FALSE). */
    private function featureEnabled(): bool
    {
        return (bool) config('academy.course_categories_enabled', false);
    }

    /** Seul academy.manage administre la taxonomie (voir docblock de classe). */
    private function canManage(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    /**
     * Catégories ordonnées, avec le nombre de cours classés (affichage seulement).
     */
    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        return CourseCategory::withCount('courses')
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /** Ouvre le formulaire de création. */
    public function create(): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    /** Ouvre le formulaire d'édition d'une catégorie existante. */
    public function edit(int $categoryId): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);

        $category = CourseCategory::find($categoryId);
        if ($category === null) {
            return;
        }

        $this->editingId = $category->id;
        $this->name      = $category->name;
        $this->color     = $category->color;
        $this->icon      = $category->icon;
        $this->showForm  = true;
    }

    /** Enregistre la catégorie (création ou mise à jour). */
    public function save(): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);

        $validated = $this->validate([
            'name'  => ['required', 'string', 'min:2', 'max:120'],
            'color' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'icon'  => ['nullable', 'string', 'max:10'],
        ], [
            'color.regex' => 'La couleur doit être un code hexadécimal valide (ex. #064E5A).',
        ]);

        if ($this->editingId !== null) {
            $category = CourseCategory::findOrFail($this->editingId);
            $category->update([
                'name'  => trim($validated['name']),
                'color' => $validated['color'] ?: null,
                'icon'  => $validated['icon'] ?: null,
            ]);
        } else {
            CourseCategory::create([
                'name'     => trim($validated['name']),
                'slug'     => $this->uniqueSlugFrom($validated['name']),
                'color'    => $validated['color'] ?: null,
                'icon'     => $validated['icon'] ?: null,
                'position' => (int) CourseCategory::max('position') + 1,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        unset($this->categories);

        session()->flash('academy_categories_status', 'Catégorie enregistrée.');
    }

    /** Demande de confirmation de suppression (1er clic - inline, jamais confirm()). */
    public function confirmDelete(int $categoryId): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);

        $this->confirmingDeleteId = $categoryId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /**
     * Supprime la catégorie (2e clic). Les cours qui y étaient classés
     * redeviennent « sans catégorie » (FK nullOnDelete), jamais supprimés.
     */
    public function remove(int $categoryId): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);

        $category = CourseCategory::find($categoryId);
        if ($category !== null) {
            $category->delete();
        }

        $this->confirmingDeleteId = null;
        unset($this->categories);

        session()->flash('academy_categories_status', 'Catégorie supprimée.');
    }

    /** Déplace une catégorie vers le haut (échange de position, clavier-friendly). */
    public function moveUp(int $categoryId): void
    {
        $this->swap($categoryId, 'up');
    }

    /** Déplace une catégorie vers le bas (échange de position, clavier-friendly). */
    public function moveDown(int $categoryId): void
    {
        $this->swap($categoryId, 'down');
    }

    private function swap(int $categoryId, string $direction): void
    {
        abort_unless($this->featureEnabled(), 404);
        abort_unless($this->canManage(), 403);

        $category = CourseCategory::find($categoryId);
        if ($category === null) {
            return;
        }

        $neighbor = CourseCategory::query()
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $category->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $category->position)->orderBy('position')
            )
            ->first();

        if ($neighbor === null) {
            return; // déjà en bout de liste
        }

        $tmp                 = $category->position;
        $category->position  = $neighbor->position;
        $neighbor->position  = $tmp;
        $category->save();
        $neighbor->save();

        unset($this->categories);
    }

    /** Annule la saisie du formulaire. */
    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name      = '';
        $this->color     = null;
        $this->icon      = null;
        $this->resetErrorBag();
    }

    /**
     * Slug unique dérivé du nom : base = Str::slug($name) (repli « categorie »
     * si vide après translittération), puis suffixe -2, -3... si collision.
     */
    private function uniqueSlugFrom(string $name): string
    {
        $base = Str::slug($name) ?: 'categorie';
        $slug = $base;
        $i    = 2;

        while (CourseCategory::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function render()
    {
        return view('academy::livewire.course-category-manager');
    }
}
