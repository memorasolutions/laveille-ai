<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Settings\Facades\Settings;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterCauser = '';

    #[Url]
    public string $filterLogName = '';

    #[Url]
    public string $filterEvent = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $detailActivityId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCauser(): void
    {
        $this->resetPage();
    }

    public function updatingFilterLogName(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterCauser = '';
        $this->filterLogName = '';
        $this->filterEvent = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function showDetail(int $id): void
    {
        $this->detailActivityId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailActivityId = null;
    }

    public function render(): \Illuminate\View\View
    {
        $activities = Activity::with('causer')
            ->when($this->search, fn ($q) => $q->where('description', 'like', '%'.$this->search.'%'))
            ->when($this->filterCauser, fn ($q) => $q
                ->where('causer_id', $this->filterCauser)
                ->where('causer_type', User::class))
            ->when($this->filterLogName, fn ($q) => $q->where('log_name', $this->filterLogName))
            ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at', 'desc')
            ->paginate((int) Settings::get('backoffice.activity_logs_per_page', 30));

        $users = User::orderBy('name')->get(['id', 'name']);
        $logNames = Activity::distinct()->orderBy('log_name')->pluck('log_name');
        $events = Activity::distinct()->whereNotNull('event')->orderBy('event')->pluck('event');
        $detailActivity = $this->detailActivityId ? Activity::with('causer')->find($this->detailActivityId) : null;

        return view('backoffice::livewire.activity-logs-table', compact('activities', 'users', 'logNames', 'events', 'detailActivity'));
    }
}
