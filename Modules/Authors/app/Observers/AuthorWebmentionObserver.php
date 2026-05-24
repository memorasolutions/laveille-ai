<?php

declare(strict_types=1);

namespace Modules\Authors\Observers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Authors\Mail\WebmentionReceivedNotificationMail;
use Modules\Authors\Models\AuthorWebmention;

class AuthorWebmentionObserver
{
    public function updated(AuthorWebmention $webmention): void
    {
        if (! $webmention->wasChanged('verified_at')) {
            return;
        }

        if ($webmention->verified_at === null) {
            return;
        }

        $originalVerifiedAt = $webmention->getOriginal('verified_at');
        if ($originalVerifiedAt !== null) {
            return;
        }

        $post = $webmention->authorPost()->with('authorProfile.user')->first();
        if (! $post || ! $post->authorProfile) {
            return;
        }

        $author = $post->authorProfile;
        if (! $author->user?->email) {
            return;
        }

        try {
            Mail::to($author->user->email)
                ->queue(new WebmentionReceivedNotificationMail($webmention, $post, $author));
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('webmention.notify.failed', [
                'webmention_id' => $webmention->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
