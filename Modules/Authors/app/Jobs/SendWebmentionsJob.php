<?php

declare(strict_types=1);

namespace Modules\Authors\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Services\WebmentionSenderService;

final class SendWebmentionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public int $authorPostId)
    {
    }

    public function handle(WebmentionSenderService $sender): void
    {
        $post = AuthorPost::with('authorProfile')->find($this->authorPostId);

        if (! $post) {
            return;
        }

        $results = $sender->sendForPost($post);

        Log::channel('daily')->info('webmention.send.job.completed', [
            'post_id' => $this->authorPostId,
            'results_count' => count($results),
        ]);
    }
}
