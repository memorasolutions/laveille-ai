<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Pages\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;
use Modules\Core\Traits\HasTableSorting;
use Modules\Pages\Models\StaticPage;
use Modules\Settings\Facades\Settings;

class StaticPagesTable extends Component
{
    use HasTableSorting, WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public string $sortBy = 'title';

    public string $sortDirection = 'asc';

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    /** Arme la confirmation inline de suppression (aucun popup navigateur natif). */
    public function confirmDeletePage(int $pageId): void
    {
        $this->confirmingDeleteId = $pageId;
    }

    /** Annule la confirmation de suppression. */
    public function cancelDeletePage(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deletePage(int $pageId): void
    {
        StaticPage::findOrFail($pageId)->delete();
        $this->confirmingDeleteId = null;
        session()->flash('success', 'Page supprimée.');
    }

    public function render(): \Illuminate\View\View
    {
        $pages = StaticPage::query()
            ->when($this->search, fn ($q) => $q->where('title->'.app()->getLocale(), 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('pages::livewire.static-pages-table', compact('pages'));
    }
}
