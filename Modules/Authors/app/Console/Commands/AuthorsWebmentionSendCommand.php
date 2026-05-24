<?php

declare(strict_types=1);

namespace Modules\Authors\Console\Commands;

use Illuminate\Console\Command;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Services\WebmentionSenderService;

class AuthorsWebmentionSendCommand extends Command
{
    protected $signature = 'authors:webmention-send {post-id : AuthorPost ID to send webmentions for}';

    protected $description = 'Send webmentions for an AuthorPost manually (admin tool)';

    public function handle(WebmentionSenderService $sender): int
    {
        $postId = (int) $this->argument('post-id');
        $post = AuthorPost::with('authorProfile')->find($postId);

        if (! $post) {
            $this->error("AuthorPost ID {$postId} not found");

            return self::FAILURE;
        }

        if (! $post->authorProfile) {
            $this->error('Post has no author profile');

            return self::FAILURE;
        }

        $this->info("📤 Sending webmentions for post #{$postId} : {$post->title}");

        $results = $sender->sendForPost($post);

        if (empty($results)) {
            $this->warn('No external URLs found in post body_html.');

            return self::SUCCESS;
        }

        $rows = array_map(
            fn ($url, $success) => [$url, $success ? '✅' : '❌'],
            array_keys($results),
            array_values($results)
        );

        $this->table(['Target URL', 'Status'], $rows);

        $successCount = count(array_filter($results));
        $this->info("📊 {$successCount}/".count($results).' webmentions envoyées avec succès');

        return self::SUCCESS;
    }
}
