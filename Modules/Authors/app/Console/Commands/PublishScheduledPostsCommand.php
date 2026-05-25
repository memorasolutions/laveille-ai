<?php

declare(strict_types=1);

namespace Modules\Authors\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorPost;

final class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'authors:publish-scheduled';

    protected $description = 'Publish scheduled AuthorPosts whose publish date has arrived';

    public function handle(): int
    {
        $scheduledPosts = AuthorPost::where('status', AuthorPost::STATUS_SCHEDULED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        $rows = [];

        foreach ($scheduledPosts as $post) {
            // Observer AuthorPostObserver détecte la transition → published et dispatch les webmentions.
            $post->update(['status' => AuthorPost::STATUS_PUBLISHED]);

            Log::channel('daily')->info('authors.scheduled.published', [
                'post_id' => $post->id,
                'slug' => $post->slug,
                'author_profile_id' => $post->author_profile_id,
            ]);

            $rows[] = [$post->slug, $post->author_profile_id, $post->published_at?->toDateTimeString()];
        }

        $count = count($rows);
        $this->info("Published {$count} scheduled post(s).");

        if ($count > 0) {
            $this->table(['Slug', 'Author', 'Published At'], $rows);
        }

        return self::SUCCESS;
    }
}
