<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Observers;

use Modules\ShortUrl\Models\ShortUrl;

class ShortUrlVisitObserver
{
    public function retrieved(ShortUrl $shortUrl): void
    {
        $route = request()?->route()?->getName();

        if ($route && str_contains($route, 'redirect')) {
            $shortUrl->withoutEvents(function () use ($shortUrl) {
                $shortUrl->update([
                    'last_visited_at' => now(),
                    'expires_at' => now()->addMonths(12),
                    'expiry_notified_at' => null,
                ]);
            });
        }
    }
}
