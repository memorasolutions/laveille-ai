<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Traits\HasBulkActions;
use Modules\SaaS\Models\Plan;

class PlansTable extends Component
{
    use HasBulkActions, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterInterval = '';

    #[Url]
    public string $filterActive = '';

    public string $sortBy = 'sort_order';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterInterval(): void
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
        $this->filterInterval = '';
        $this->filterActive = '';
        $this->resetPage();
    }

    public function toggleActive(int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);
        $status = $plan->is_active ? 'activé' : 'désactivé';
        $this->dispatch('toast', type: 'success', message: "Plan {$plan->name} {$status}.");
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
            'activate' => Plan::whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => Plan::whereIn('id', $ids)->update(['is_active' => false]),
            'delete' => Plan::whereIn('id', $ids)->delete(),
            default => null,
        };
    }

    protected function getBulkPageIds(): array
    {
        return Plan::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")
            ))
            ->when($this->filterInterval, fn ($q) => $q->where('interval', $this->filterInterval))
            ->when($this->filterActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $plans = Plan::when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('slug', 'like', "%{$this->search}%")
        ))
            ->when($this->filterInterval, fn ($q) => $q->where('interval', $this->filterInterval))
            ->when($this->filterActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        return view('backoffice::livewire.plans-table', compact('plans'));
    }
}
