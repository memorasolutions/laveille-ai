<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Console;

use Illuminate\Console\Command;
use Modules\ShortUrl\Models\ShortUrl;

class CleanupExpiredCommand extends Command
{
    /**
     * Délai de grâce (en jours) avant suppression définitive des liens QR fixes.
     *
     * Entre l'expiration et +90j : le lien reste en base → scan affiche « expiré » (410).
     * Après +90j : suppression → scan retourne « introuvable » (404).
     */
    public const QR_GRACE_DAYS = 90;

    protected $signature = 'shorturl:cleanup-expired';

    protected $description = 'Supprime les liens courts expirés et notifie les propriétaires';

    public function handle(): int
    {
        $deleted = 0;
        $warned = 0;

        // 1. Notifier les propriétaires 30 jours avant expiration
        //    → Seulement les liens normaux (auto_extend=true ou NULL).
        //    → Les liens QR fixes (auto_extend=false) sont EXCLUS : leur date est fixée
        //      intentionnellement ; une notif « expire bientôt » n'a pas de sens ici.
        //    → Guard user_id NOT NULL : on ne tente jamais de notifier un lien anonyme.
        $expiring = ShortUrl::where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->whereNotNull('user_id')
            ->where(function ($query) {
                $query->whereNull('auto_extend')
                      ->orWhere('auto_extend', true);
            })
            ->whereNull('expiry_notified_at')
            ->with('user')
            ->get();

        foreach ($expiring as $shortUrl) {
            if ($shortUrl->user?->email && class_exists(\Modules\ShortUrl\Notifications\ShortUrlExpiringNotification::class)) {
                $shortUrl->user->notify(new \Modules\ShortUrl\Notifications\ShortUrlExpiringNotification($shortUrl));
                $warned++;
            }
            $shortUrl->update(['expiry_notified_at' => now()]);
        }

        // 2a. Supprimer les liens normaux expirés (auto_extend=true ou NULL) — comportement actuel.
        $expiredNormal = ShortUrl::where('expires_at', '<', now())
            ->where(function ($query) {
                $query->whereNull('auto_extend')
                      ->orWhere('auto_extend', true);
            })
            ->get();

        foreach ($expiredNormal as $shortUrl) {
            if ($shortUrl->user?->email && class_exists(\Modules\ShortUrl\Notifications\ShortUrlExpiredNotification::class)) {
                $shortUrl->user->notify(new \Modules\ShortUrl\Notifications\ShortUrlExpiredNotification($shortUrl));
            }
            $shortUrl->delete();
            $deleted++;
        }

        // 2b. Supprimer les liens QR fixes (auto_extend=false) uniquement APRÈS la grâce de 90 jours.
        //     Pendant la grâce : le lien reste en base, le scan renvoie « expiré » (reason=expired).
        //     Après la grâce : suppression → scan renvoie « introuvable » (reason=notfound).
        $expiredQr = ShortUrl::where('auto_extend', false)
            ->where('expires_at', '<', now()->subDays(self::QR_GRACE_DAYS))
            ->get();

        foreach ($expiredQr as $shortUrl) {
            // Guard : notif uniquement si propriétaire identifié (jamais sur anonymes)
            if ($shortUrl->user?->email && class_exists(\Modules\ShortUrl\Notifications\ShortUrlExpiredNotification::class)) {
                $shortUrl->user->notify(new \Modules\ShortUrl\Notifications\ShortUrlExpiredNotification($shortUrl));
            }
            $shortUrl->delete();
            $deleted++;
        }

        // Comptage informatif : liens QR fixes actuellement dans la période de grâce.
        $qrInGrace = ShortUrl::where('auto_extend', false)
            ->where('expires_at', '<', now())
            ->where('expires_at', '>=', now()->subDays(self::QR_GRACE_DAYS))
            ->count();

        $this->info("Liens supprimés : {$deleted}. Avertissements envoyés : {$warned}. Liens QR en grâce : {$qrInGrace}.");

        return self::SUCCESS;
    }
}
