<?php

declare(strict_types=1);

namespace Modules\Authors\Observers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Jobs\SendWebmentionsJob;
use Modules\Authors\Models\AuthorPost;

class AuthorPostObserver
{
    public function saved(AuthorPost $post): void
    {
        $statusChanged = $post->wasChanged('status') && $post->status === 'published';
        $bodyChanged = $post->wasChanged('body_html') && $post->status === 'published';

        if (! $statusChanged && ! $bodyChanged) {
            return;
        }

        if (! $post->authorProfile) {
            return;
        }

        try {
            Bus::dispatch(new SendWebmentionsJob($post->id));
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('webmention.observer.dispatch.failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
