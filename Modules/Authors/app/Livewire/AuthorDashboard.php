<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuthorDashboard extends Component
{
    public string $activeTab = 'composer';

    public ?int $authorProfileId = null;

    public function mount(?int $authorProfileId = null): void
    {
        $this->authorProfileId = $authorProfileId;
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['composer', 'articles', 'curation', 'builders', 'parametres', 'stats', 'subscribers', 'affiliates', 'historique'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function getSubscribersStats(AuthorProfile $author): array
    {
        $base = AuthorSubscriber::where('author_profile_id', $author->id);

        return [
            'total' => (clone $base)->count(),
            'confirmed' => (clone $base)->confirmed()->count(),
            'pending' => (clone $base)->pending()->count(),
            'unsubscribed' => (clone $base)->whereNotNull('unsubscribed_at')->count(),
            'last_7_days' => (clone $base)->where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    public function exportSubscribersCsv(): StreamedResponse
    {
        $authorId = $this->authorProfileId;

        return response()->streamDownload(function () use ($authorId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'source', 'locale', 'confirmed_at', 'unsubscribed_at', 'created_at']);
            AuthorSubscriber::where('author_profile_id', $authorId)
                ->orderBy('created_at')
                ->chunk(200, function ($subscribers) use ($handle) {
                    foreach ($subscribers as $sub) {
                        fputcsv($handle, [
                            $sub->email,
                            $sub->source,
                            $sub->locale,
                            $sub->confirmed_at?->toDateTimeString(),
                            $sub->unsubscribed_at?->toDateTimeString(),
                            $sub->created_at->toDateTimeString(),
                        ]);
                    }
                });
            fclose($handle);
        }, 'subscribers_'.$authorId.'_'.now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function render()
    {
        $author = $this->authorProfileId ? AuthorProfile::find($this->authorProfileId) : null;
        $subscribersStats = ($this->activeTab === 'subscribers' && $author)
            ? $this->getSubscribersStats($author)
            : null;

        return view('authors::livewire.author-dashboard', compact('author', 'subscribersStats'));
    }
}
