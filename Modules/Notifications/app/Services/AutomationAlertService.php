<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutomationAlertService
{
    public static function fire(string $source, string $title, string $message, array $context = []): void
    {
        try {
            $cacheKey = 'automation_alert:' . md5($source . ':' . $title);

            // ACTION : journaliser l'étouffement au lieu d'un retour nu.
            // MCP: SELF (correctif de journalisation, < 5 lignes)
            // RAISON : ce retour nu a coûté 17 heures d'aveuglement (25-26 août 2026, trois jobs
            // en échec sans un seul courriel) - rien ne distinguait après coup un étouffement
            // légitime du régulateur anti-spam d'une panne muette. Une seule lecture de cache
            // (plutôt que has() puis get()) : la valeur porte l'horodatage d'expiration, pas un
            // simple booléen, pour pouvoir calculer le temps restant ci-dessous, et il n'y a pas
            // de fenêtre où la clé expirerait entre deux appels distincts.
            $cacheExpiresAt = Cache::get($cacheKey);

            if ($cacheExpiresAt !== null) {
                $secondesAvantExpiration = is_int($cacheExpiresAt)
                    ? max(0, $cacheExpiresAt - now()->getTimestamp())
                    : null; // valeur héritée d'avant ce correctif (booléen) : temps inconnu, pas une erreur.

                Log::channel('automation_alerts')->info(
                    '[AutomationAlertService] Alerte étouffée par le régulateur anti-spam.',
                    [
                        'issue' => 'etouffee',
                        'source' => $source,
                        'title' => $title,
                        'cache_key' => $cacheKey,
                        'secondes_avant_expiration' => $secondesAvantExpiration,
                    ]
                );

                return;
            }

            $admin = config('app.superadmin_email');

            if (empty($admin)) {
                // ACTION : router ce journal déjà existant vers le canal dédié à niveau fixe.
                // MCP: SELF (changement de canal, < 5 lignes)
                // RAISON : Log::warning sur le canal par défaut est avalé par LOG_LEVEL=error en
                // production (warning est sous error) - ce retour anticipé était donc tout aussi
                // muet que le retour nu ci-dessus, et pour la même raison de fond (voir
                // docs/CONTRAINTES-SOUS-AGENTS.md, section 6).
                Log::channel('automation_alerts')->warning(
                    '[AutomationAlertService] superadmin_email non configuré, alerte ignorée.',
                    [
                        'issue' => 'superadmin_email_manquant',
                        'source' => $source,
                        'title' => $title,
                    ]
                );

                return;
            }

            $subject = "[laveille.ai] ALERTE: {$title}";

            $body = implode("\n", [
                '========================================',
                ' ALERTE AUTOMATION',
                '========================================',
                '',
                'Date (UTC) : ' . now()->utc()->toDateTimeString(),
                'Source     : ' . $source,
                'Titre      : ' . $title,
                '',
                '--- Message ---',
                $message,
                '',
                '--- Contexte ---',
                json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '',
                '========================================',
            ]);

            Mail::raw($body, fn ($m) => $m->to($admin)->subject($subject));

            // La valeur mise en cache est l'horodatage d'expiration lui-même (et non un simple
            // true) : c'est ce qui permet, plus haut, de journaliser le temps restant exact
            // quand une alerte identique est étouffée pendant cette fenêtre de 15 minutes.
            $expiresAt = now()->addMinutes(15);
            Cache::put($cacheKey, $expiresAt->getTimestamp(), $expiresAt);

            // ACTION : journaliser l'envoi réussi, symétriquement à l'échec journalisé plus bas.
            // MCP: SELF (correctif de journalisation, < 5 lignes)
            // RAISON : sans cette ligne, un envoi réussi et une exception avalée en amont (avant
            // même d'atteindre ce service) restent indiscernables dans les journaux - seule cette
            // trace prouve que le courriel est réellement parti.
            Log::channel('automation_alerts')->info(
                '[AutomationAlertService] Alerte envoyée.',
                [
                    'issue' => 'envoyee',
                    'source' => $source,
                    'title' => $title,
                    'destinataire' => $admin,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[AutomationAlertService] Impossible d\'envoyer l\'alerte.', [
                'source' => $source,
                'title' => $title,
                'message' => $message,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
