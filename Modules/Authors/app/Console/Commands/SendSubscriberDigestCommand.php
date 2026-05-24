<?php

declare(strict_types=1);

namespace Modules\Authors\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Authors\Mail\SubscriberDigestMail;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorSubscriber;

final class SendSubscriberDigestCommand extends Command
{
    protected $signature = 'authors:subscriber-digest {--subscriber-id= : Limit to a specific subscriber}';

    protected $description = 'Send the weekly new-articles digest to confirmed subscribers';

    public function handle(): int
    {
        $query = AuthorSubscriber::query()
            ->whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->with('authorProfile.user');

        if ($subscriberId = $this->option('subscriber-id')) {
            $query->where('id', (int) $subscriberId);
        }

        $results = [];

        foreach ($query->cursor() as $subscriber) {
            $author = $subscriber->authorProfile;
            if ($author === null) {
                continue;
            }

            $since = $subscriber->last_digest_at ?? $subscriber->confirmed_at;

            $posts = AuthorPost::published()
                ->public()
                ->where('author_profile_id', $subscriber->author_profile_id)
                ->when($since !== null, fn ($q) => $q->where('published_at', '>', $since))
                ->orderByDesc('published_at')
                ->get();

            if ($posts->isEmpty()) {
                $results[] = [$subscriber->email, $author->display_name ?? $author->slug, 0, 'skipped'];

                continue;
            }

            Mail::queue(new SubscriberDigestMail($subscriber, $author, $posts));

            $subscriber->last_digest_at = now();
            $subscriber->save();

            Log::channel('daily')->info('authors.subscriber_digest.sent', [
                'subscriber_id' => $subscriber->id,
                'author_profile_id' => $subscriber->author_profile_id,
                'posts_count' => $posts->count(),
            ]);

            $results[] = [$subscriber->email, $author->display_name ?? $author->slug, $posts->count(), 'sent'];
        }

        if ($results !== []) {
            $this->table(['Abonné', 'Auteur', 'Articles', 'Statut'], $results);
        }

        $this->info('Subscriber digest run complete: '.count($results).' subscriber(s) processed.');

        return self::SUCCESS;
    }
}
