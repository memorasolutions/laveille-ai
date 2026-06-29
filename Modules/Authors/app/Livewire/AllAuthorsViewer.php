<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;

class AllAuthorsViewer extends Component
{
    use WithInfiniteScroll, WithPagination;

    public string $search = '';

    public string $tierFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatingTierFilter(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function render()
    {
        $query = AuthorProfile::query()->with('user');

        if ($this->search !== '') {
            $escaped = addcslashes($this->search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('slug', 'like', "%{$escaped}%")
                    ->orWhere('display_name', 'like', "%{$escaped}%");
            });
        }

        if ($this->tierFilter !== 'all') {
            $query->where('tier', $this->tierFilter);
        }

        $authors = $query->latest('created_at')->paginate($this->perPage);

        $stats = $authors->mapWithKeys(function (AuthorProfile $author): array {
            return [
                $author->id => [
                    'posts_count' => AuthorPost::where('author_profile_id', $author->id)->count(),
                    'published_count' => AuthorPost::where('author_profile_id', $author->id)->published()->count(),
                    'comments_count' => AuthorComment::where('author_profile_id', $author->id)->count(),
                    'subscribers_count' => AuthorSubscriber::where('author_profile_id', $author->id)->confirmed()->count(),
                ],
            ];
        })->toArray();

        return view('authors::livewire.all-authors-viewer', compact('authors', 'stats'));
    }
}
