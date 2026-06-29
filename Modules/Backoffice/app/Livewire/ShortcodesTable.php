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
use Modules\Core\Traits\HasBulkActions;
use Modules\Core\Traits\HasTableSorting;
use Modules\Editor\Models\Shortcode;
use Modules\Settings\Facades\Settings;

class ShortcodesTable extends Component
{
    use HasBulkActions, HasTableSorting, WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortBy = 'tag';

    #[Url]
    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    protected function getBulkActions(): array
    {
        return [
            'delete' => __('Supprimer'),
        ];
    }

    protected function handleBulkAction(string $action, array $ids): void
    {
        match ($action) {
            'delete' => Shortcode::whereIn('id', $ids)->delete(),
            default => null,
        };
    }

    protected function getBulkPageIds(): array
    {
        return Shortcode::query()
            ->when($this->search, function ($q) {
                $q->where('tag', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) $this->perPage)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render()
    {
        $shortcodes = Shortcode::query()
            ->when($this->search, function ($q) {
                $q->where('tag', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) $this->perPage);

        return view('backoffice::livewire.shortcodes-table', ['shortcodes' => $shortcodes]);
    }
}
