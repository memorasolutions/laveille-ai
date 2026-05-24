<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorPushSubscription;

final class WebPushService
{
    public function subscribe(AuthorProfile $author, array $subscription): AuthorPushSubscription
    {
        if (! isset($subscription['endpoint'])) {
            throw new InvalidArgumentException('endpoint missing');
        }

        $keys = $subscription['keys'] ?? [];
        if (! isset($keys['p256dh']) || ! isset($keys['auth'])) {
            throw new InvalidArgumentException('Missing required keys: p256dh and auth');
        }

        return AuthorPushSubscription::updateOrCreate(
            ['endpoint' => $subscription['endpoint']],
            [
                'author_profile_id' => $author->id,
                'public_key' => $keys['p256dh'],
                'auth_token' => $keys['auth'],
                'content_encoding' => 'aes128gcm',
            ]
        );
    }

    public function unsubscribe(string $endpoint): bool
    {
        $sub = AuthorPushSubscription::where('endpoint', $endpoint)->first();

        return $sub ? (bool) $sub->delete() : false;
    }

    public function isEnabled(): bool
    {
        return ! empty(config('services.webpush.vapid_public_key'))
            && ! empty(config('services.webpush.vapid_private_key'));
    }

    public static function vapidPublicKey(): ?string
    {
        return config('services.webpush.vapid_public_key');
    }

    public function send(AuthorProfile $author, string $title, string $body, ?string $url = null): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $subscriptions = AuthorPushSubscription::where('author_profile_id', $author->id)->get();
        $sent = 0;

        foreach ($subscriptions as $sub) {
            try {
                Log::channel('daily')->info('webpush.send.stub', [
                    'endpoint' => $sub->endpoint,
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                ]);
                $sent++;
            } catch (\Throwable $e) {
                // Silent fail MVP — real send via minteractive/web-push S115+
            }
        }

        return $sent;
    }
}
