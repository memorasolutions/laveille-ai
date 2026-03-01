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
use Modules\Newsletter\Models\Subscriber;

class SubscribersTable extends Component
{
    use HasBulkActions, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

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

    public function delete(int $id): void
    {
        Subscriber::find($id)?->delete();
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
            'delete' => Subscriber::whereIn('id', $ids)->delete(),
            default => null,
        };
    }

    protected function getBulkPageIds(): array
    {
        return Subscriber::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('email', 'like', '%'.$this->search.'%')
                ->orWhere('name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'pending', fn ($q) => $q->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest()
            ->paginate(20)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $query = Subscriber::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('email', 'like', '%'.$this->search.'%')
                ->orWhere('name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'pending', fn ($q) => $q->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest();

        $subscribers = $query->paginate(20);
        $totalCount = Subscriber::count();
        $activeCount = Subscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count();
        $pendingCount = Subscriber::whereNull('confirmed_at')->whereNull('unsubscribed_at')->count();
        $unsubscribedCount = Subscriber::whereNotNull('unsubscribed_at')->count();

        return view('backoffice::livewire.subscribers-table', compact(
            'subscribers',
            'totalCount',
            'activeCount',
            'pendingCount',
            'unsubscribedCount'
        ));
    }
}
