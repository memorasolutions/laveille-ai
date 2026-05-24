<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorSubscriber;
use Modules\Authors\Models\AuthorWebmention;

class AuthorRecentNotifications extends Component
{
    public int $authorProfileId;

    public function mount(int $authorProfileId): void
    {
        $this->authorProfileId = $authorProfileId;
    }

    public function render()
    {
        $events = collect();

        $comments = AuthorComment::where('author_profile_id', $this->authorProfileId)
            ->latest()
            ->limit(5)
            ->get();
        foreach ($comments as $c) {
            $events->push([
                'type' => 'comment',
                'icon' => '💬',
                'label' => 'Commentaire de '.($c->author_name ?? 'Anonyme'),
                'detail' => Str::limit((string) $c->body, 60),
                'created_at' => $c->created_at,
            ]);
        }

        if (class_exists(AuthorWebmention::class)) {
            $postIds = AuthorPost::where('author_profile_id', $this->authorProfileId)->pluck('id');
            $webmentions = AuthorWebmention::whereIn('author_post_id', $postIds)
                ->whereNotNull('verified_at')
                ->latest('verified_at')
                ->limit(5)
                ->get();
            foreach ($webmentions as $wm) {
                $events->push([
                    'type' => 'webmention',
                    'icon' => '🔗',
                    'label' => 'Mention web : '.($wm->source_author_name ?? 'Anonyme'),
                    'detail' => Str::limit((string) ($wm->source_excerpt ?? 'Backlink ajouté'), 60),
                    'created_at' => $wm->verified_at,
                ]);
            }
        }

        $subscribers = AuthorSubscriber::where('author_profile_id', $this->authorProfileId)
            ->whereNotNull('confirmed_at')
            ->latest('confirmed_at')
            ->limit(5)
            ->get();
        foreach ($subscribers as $s) {
            $events->push([
                'type' => 'subscriber',
                'icon' => '📬',
                'label' => 'Nouveau abonné : '.$s->email,
                'detail' => 'Source : '.($s->source ?? 'inline'),
                'created_at' => $s->confirmed_at,
            ]);
        }

        $events = $events->sortByDesc('created_at')->take(10)->values();

        return view('authors::livewire.author-recent-notifications', compact('events'));
    }
}
