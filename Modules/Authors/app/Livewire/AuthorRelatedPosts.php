<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Models\AuthorPost;

final class AuthorRelatedPosts extends Component
{
    public int $authorProfileId;

    public int $currentPostId;

    public array $currentTags = [];

    public function mount(int $authorProfileId, int $currentPostId, array $currentTags = []): void
    {
        $this->authorProfileId = $authorProfileId;
        $this->currentPostId = $currentPostId;
        $this->currentTags = $currentTags;
    }

    public function render()
    {
        $query = AuthorPost::published()
            ->public()
            ->with('authorProfile:id,slug,display_name')
            ->where('author_profile_id', $this->authorProfileId)
            ->where('id', '!=', $this->currentPostId);

        if (! empty($this->currentTags)) {
            $tags = $this->currentTags;
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $relatedPosts = $query
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('authors::livewire.author-related-posts', compact('relatedPosts'));
    }
}
