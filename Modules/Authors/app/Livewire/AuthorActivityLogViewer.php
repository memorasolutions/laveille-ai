<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Spatie\Activitylog\Models\Activity;

class AuthorActivityLogViewer extends Component
{
    use WithPagination;

    public int $authorProfileId;

    public string $period = '7d';

    public string $logName = 'all';

    public function mount(int $authorProfileId): void
    {
        $this->authorProfileId = $authorProfileId;
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['today', '7d', '30d', 'all'], true)) {
            $this->period = $period;
            $this->resetPage();
        }
    }

    public function setLogName(string $logName): void
    {
        if (in_array($logName, ['all', 'author_post', 'author_profile'], true)) {
            $this->logName = $logName;
            $this->resetPage();
        }
    }

    public function render()
    {
        $author = AuthorProfile::find($this->authorProfileId);

        $postIds = $author
            ? AuthorPost::where('author_profile_id', $author->id)->pluck('id')
            : collect();

        $query = Activity::query()->where(function ($q) use ($postIds, $author) {
            $q->where(function ($q2) use ($postIds) {
                $q2->where('subject_type', AuthorPost::class)
                    ->whereIn('subject_id', $postIds);
            });

            if ($author) {
                $q->orWhere(function ($q3) use ($author) {
                    $q3->where('subject_type', AuthorProfile::class)
                        ->where('subject_id', $author->id);
                });
            }
        });

        match ($this->period) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            default => null,
        };

        if ($this->logName !== 'all') {
            $query->where('log_name', $this->logName);
        }

        $activities = $query->latest()->paginate(20);

        return view('authors::livewire.author-activity-log-viewer', compact('activities'));
    }
}
