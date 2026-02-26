<?php

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\HasBulkActions;
use Modules\Blog\Models\Category;

class CategoriesTable extends Component
{
    use HasBulkActions, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterActive = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterActive(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterActive = '';
        $this->resetPage();
    }

    public function toggleActive(int $categoryId): void
    {
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

    protected function handleBulkAction(string $action, array $ids): void
    {
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
            ->paginate(15)
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
            ->paginate(15);

        return view('backoffice::livewire.categories-table', compact('categories'));
    }
}
