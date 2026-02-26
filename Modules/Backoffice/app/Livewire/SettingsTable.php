<?php

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Settings\Models\Setting;

class SettingsTable extends Component
{
    use WithPagination;

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
    }

    public function updatingFilterGroup(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterGroup = '';
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
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
            ->paginate(20);

        return view('backoffice::livewire.settings-table', compact('settings', 'groups'));
    }
}
