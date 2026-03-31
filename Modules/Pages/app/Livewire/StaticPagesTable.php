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
use Modules\Core\Traits\HasTableSorting;
use Modules\Pages\Models\StaticPage;
use Modules\Settings\Facades\Settings;

class StaticPagesTable extends Component
{
    use HasTableSorting, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public string $sortBy = 'title';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function deletePage(int $pageId): void
    {
        StaticPage::findOrFail($pageId)->delete();
        session()->flash('success', 'Page supprimée.');
    }

    public function render(): \Illuminate\View\View
    {
        $pages = StaticPage::query()
            ->when($this->search, fn ($q) => $q->where('title->'.app()->getLocale(), 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) Settings::get('pages.static_pages_per_page', 15));

        return view('pages::livewire.static-pages-table', compact('pages'));
    }
}
