<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Observers;

use Modules\ShortUrl\Models\ShortUrl;

class ShortUrlVisitObserver
{
    public function retrieved(ShortUrl $shortUrl): void
    {
        // Ne mettre à jour que lors d'une redirection shorturl (pas dans l'admin ou la liste)
        $route = request()?->route()?->getName() ?? '';

        if (str_contains($route, 'redirect') || str_contains($route, 'veille.la-redirect') || str_contains($route, 'go3.ca-redirect')) {
            ShortUrl::withoutEvents(function () use ($shortUrl) {
                $shortUrl->forceFill([
                    'last_visited_at' => now(),
                    'expires_at' => now()->addMonths(12),
                    'expiry_notified_at' => null,
                ])->saveQuietly();
            });
        }
    }
}
