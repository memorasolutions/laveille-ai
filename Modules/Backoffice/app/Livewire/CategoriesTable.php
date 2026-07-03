<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;
use Modules\Blog\Models\Category;
use Modules\Settings\Facades\Settings;
use Modules\Core\Traits\HasBulkActions;

class CategoriesTable extends Component
{
    use HasBulkActions, WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterActive = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatingFilterActive(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterActive = '';
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    /**
     * Requiert update_articles (cohérent avec la route PUT categories/{category} du module Blog).
     */
    public function toggleActive(int $categoryId): void
    {
        abort_if(! auth()->user()?->can('update_articles'), 403);

        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
        $status = $category->is_active ? 'activée' : 'désactivée';
        $this->dispatch('toast', type: 'success', message: "Catégorie {$category->name} {$status}.");
    }

    protected function getBulkActions(): array
    {
        return [
            'activate' => __('Activer'),
            'deactivate' => __('Désactiver'),
            'delete' => __('Supprimer'),
        ];
    }

    /**
     * Requiert delete_articles (delete) ou update_articles (activate/deactivate),
     * cohérent avec les routes DELETE/PUT categories du module Blog.
     */
    protected function handleBulkAction(string $action, array $ids): void
    {
        if ($action === 'delete') {
            abort_if(! auth()->user()?->can('delete_articles'), 403);
        } else {
            abort_if(! auth()->user()?->can('update_articles'), 403);
        }

        match ($action) {
            'activate' => Category::whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => Category::whereIn('id', $ids)->update(['is_active' => false]),
            'delete' => Category::whereIn('id', $ids)->delete(),
            default => null,
        };
    }

    protected function getBulkPageIds(): array
    {
        return Category::query()
            ->when($this->search, fn ($q) => $q->where('name->'.app()->getLocale(), 'like', '%'.$this->search.'%'))
            ->when($this->filterActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy('name')
            ->paginate((int) $this->perPage)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $categories = Category::withCount('articles')
            ->when($this->search, fn ($q) => $q->where('name->'.app()->getLocale(), 'like', '%'.$this->search.'%'))
            ->when($this->filterActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy('name')
            ->paginate((int) $this->perPage);

        return view('backoffice::livewire.categories-table', compact('categories'));
    }
}
