<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Authors\Models\AuthorPost;

class AuthorSearch extends Component
{
    public int $authorProfileId;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    public function mount(int $authorProfileId): void
    {
        $this->authorProfileId = $authorProfileId;
    }

    public function search(): void
    {
        // Trigger render via $this->query change
    }

    public function updatedQuery(): void
    {
        // Auto-refresh on debounce
    }

    public function render()
    {
        $results = collect();
        $q = trim($this->query);

        if (mb_strlen($q) >= 2) {
            $escaped = addcslashes($q, '%_\\');
            $results = AuthorPost::query()
                ->where('author_profile_id', $this->authorProfileId)
                ->published()
                ->public()
                ->where(function ($qb) use ($escaped) {
                    $qb->where('title', 'like', "%{$escaped}%")
                        ->orWhere('body_markdown', 'like', "%{$escaped}%")
                        ->orWhere('excerpt', 'like', "%{$escaped}%");
                })
                ->orderByDesc('published_at')
                ->limit(10)
                ->get();
        }

        return view('authors::livewire.author-search', ['results' => $results]);
    }
}
