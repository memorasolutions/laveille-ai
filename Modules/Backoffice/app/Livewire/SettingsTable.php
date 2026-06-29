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
use Modules\Core\Traits\HasTableSorting;
use Modules\Settings\Facades\Settings;
use Modules\Settings\Models\Setting;

class SettingsTable extends Component
{
    use HasTableSorting, WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterGroup = '';

    public string $sortBy = 'key';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatingFilterGroup(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterGroup = '';
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function render(): \Illuminate\View\View
    {
        $groups = Setting::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->orderBy('group')
            ->pluck('group');

        $settings = Setting::when($this->search, fn ($q) => $q->where(
            fn ($q2) => $q2->where('key', 'like', "%{$this->search}%")
                ->orWhere('value', 'like', "%{$this->search}%")
        ))
            ->when($this->filterGroup, fn ($q) => $q->where('group', $this->filterGroup))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) $this->perPage);

        return view('backoffice::livewire.settings-table', compact('settings', 'groups'));
    }
}
