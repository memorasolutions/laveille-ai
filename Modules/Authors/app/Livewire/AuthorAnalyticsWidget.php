<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;

final class AuthorAnalyticsWidget extends Component
{
    public int $authorProfileId;

    public function mount(int $authorProfileId): void
    {
        $profile = AuthorProfile::findOrFail($authorProfileId);
        abort_if($profile->user_id !== auth()->id(), 403);

        $this->authorProfileId = $authorProfileId;
    }

    public function render(): View
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(29);

        $dateRange = collect(range(0, 29))->map(fn (int $i) => $start->copy()->addDays($i)->format('Y-m-d'));

        $postsSeries = $this->buildSeries(
            AuthorPost::published()
                ->where('author_profile_id', $this->authorProfileId)
                ->whereBetween('published_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->get()
                ->groupBy(fn (AuthorPost $p) => $p->published_at?->format('Y-m-d')),
            $dateRange
        );

        $commentsSeries = $this->buildSeries(
            AuthorComment::where('author_profile_id', $this->authorProfileId)
                ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->get()
                ->groupBy(fn (AuthorComment $c) => $c->created_at?->format('Y-m-d')),
            $dateRange
        );

        $subscribersSeries = $this->buildSeries(
            AuthorSubscriber::where('author_profile_id', $this->authorProfileId)
                ->whereNotNull('confirmed_at')
                ->whereBetween('confirmed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->get()
                ->groupBy(fn (AuthorSubscriber $s) => $s->confirmed_at?->format('Y-m-d')),
            $dateRange
        );

        return view('authors::livewire.author-analytics-widget', [
            'postsSeries' => $postsSeries,
            'commentsSeries' => $commentsSeries,
            'subscribersSeries' => $subscribersSeries,
            'postsTotal' => array_sum($postsSeries),
            'commentsTotal' => array_sum($commentsSeries),
            'subscribersTotal' => array_sum($subscribersSeries),
            'postsPath' => $this->sparklinePoints($postsSeries),
            'commentsPath' => $this->sparklinePoints($commentsSeries),
            'subscribersPath' => $this->sparklinePoints($subscribersSeries),
        ]);
    }

    /**
     * @return list<int>
     */
    private function buildSeries(Collection $grouped, Collection $dateRange): array
    {
        $series = [];
        foreach ($dateRange as $date) {
            $series[] = $grouped->has($date) ? $grouped->get($date)->count() : 0;
        }

        return $series;
    }

    /**
     * @param  list<int>  $series
     */
    private function sparklinePoints(array $series): string
    {
        $max = max($series) ?: 1;
        $points = [];
        foreach ($series as $i => $value) {
            $x = round(($i / 29) * 100, 2);
            $y = round(30 - ($value / $max * 28), 2);
            $points[] = $x.','.$y;
        }

        return implode(' ', $points);
    }
}
