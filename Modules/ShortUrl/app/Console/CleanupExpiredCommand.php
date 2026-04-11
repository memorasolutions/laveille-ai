<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Console;

use Illuminate\Console\Command;
use Modules\ShortUrl\Models\ShortUrl;

class CleanupExpiredCommand extends Command
{
    protected $signature = 'shorturl:cleanup-expired';

    protected $description = 'Supprime les liens courts expirés et notifie les propriétaires';

    public function handle(): int
    {
        $deleted = 0;
        $warned = 0;

        // 1. Avertir les liens qui expirent dans 30 jours (pas encore notifiés)
        $expiring = ShortUrl::where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->whereNull('expiry_notified_at')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        foreach ($expiring as $shortUrl) {
            if ($shortUrl->user?->email && class_exists(\Modules\ShortUrl\Notifications\ShortUrlExpiringNotification::class)) {
                $shortUrl->user->notify(new \Modules\ShortUrl\Notifications\ShortUrlExpiringNotification($shortUrl));
                $warned++;
            }
            $shortUrl->update(['expiry_notified_at' => now()]);
        }

        // 2. Supprimer les liens expirés
        $expired = ShortUrl::where('expires_at', '<', now())->get();

        foreach ($expired as $shortUrl) {
            if ($shortUrl->user?->email && class_exists(\Modules\ShortUrl\Notifications\ShortUrlExpiredNotification::class)) {
                $shortUrl->user->notify(new \Modules\ShortUrl\Notifications\ShortUrlExpiredNotification($shortUrl));
            }
            $shortUrl->delete();
            $deleted++;
        }

        $this->info("Liens supprimés : {$deleted}. Avertissements envoyés : {$warned}.");

        return self::SUCCESS;
    }
}
