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
use Modules\Newsletter\Models\Campaign;
use Modules\Settings\Facades\Settings;
use Modules\Newsletter\States\DraftCampaignState;
use Modules\Newsletter\States\SentCampaignState;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class CampaignsTable extends Component
{
    use HasBulkActions, WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

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

    /**
     * Requiert update_campaigns (cohérent avec la route PUT campaigns/{campaign}).
     */
    public function changeStatus(int $campaignId, string $status): void
    {
        abort_if(! auth()->user()?->can('update_campaigns'), 403);

        $stateMap = [
            'draft' => DraftCampaignState::class,
            'sent' => SentCampaignState::class,
        ];

        if (! isset($stateMap[$status])) {
            return;
        }

        $campaign = Campaign::findOrFail($campaignId);

        try {
            $campaign->status->transitionTo($stateMap[$status]);
            $this->dispatch('toast', type: 'success', message: "Campagne « {$campaign->subject} » → {$status}.");
        } catch (CouldNotPerformTransition) {
            $this->dispatch('toast', type: 'error', message: "Transition vers « {$status} » non autorisée.");
        }
    }

    protected function getBulkActions(): array
    {
        return [
            'delete' => __('Supprimer'),
        ];
    }

    /**
     * Requiert delete_campaigns (cohérent avec la route DELETE campaigns/{campaign}).
     */
    protected function handleBulkAction(string $action, array $ids): void
    {
        if ($action === 'delete') {
            abort_if(! auth()->user()?->can('delete_campaigns'), 403);
        }

        match ($action) {
            'delete' => Campaign::whereIn('id', $ids)->delete(),
            default => null,
        };
    }

    protected function getBulkPageIds(): array
    {
        return Campaign::query()
            ->when($this->search, fn ($q) => $q->where('subject', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate((int) $this->perPage)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $campaigns = Campaign::query()
            ->when($this->search, fn ($q) => $q->where('subject', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate((int) $this->perPage);

        return view('backoffice::livewire.campaigns-table', compact('campaigns'));
    }
}
