<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Sentry\Event;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Round 13 (skill /100 - module Decido) : Sentry\Integration\RequestIntegration capture
 * INCONDITIONNELLEMENT l'URL complète de la requête (event.request.url) sur CHAQUE exception
 * rapportée, même quand send_default_pii=false - ce flag ne protège que cookies/headers/IP,
 * jamais l'URL (voir vendor/sentry/sentry/src/Integration/RequestIntegration.php:129,
 * `$requestData['url'] = (string) $request->getUri();` hors de toute condition PII).
 *
 * Toute route encodant un jeton d'accès dans le CHEMIN de l'URL (pas en query string, donc
 * invisible d'un simple audit "pas de token en paramètre") fuite ainsi ce jeton vers Sentry
 * (service tiers hors UE) à la première exception rencontrée pendant le traitement - ex.
 * /decido/{poll}/gerer/{adminToken} (jeton = contrôle total du sondage : clôture, export des
 * pseudonymes des votants, lien court). Même famille de fuite que le round 12 (jeton admin vers
 * GA4 via page_location), mais via la télémétrie d'erreurs plutôt que l'analytics - un angle
 * distinct puisque GA4 ne s'exécute que côté navigateur (JS) alors que Sentry côté serveur
 * capture même les requêtes qui échouent avant tout rendu de vue.
 *
 * Service générique et réutilisable (DRY) : tout module exposant un jeton dans le chemin d'URL
 * ajoute son propre motif à SENSITIVE_URL_PATTERNS au lieu de dupliquer un before_send Sentry
 * local. Branché via config/sentry.php (clé 'before_send'), fusionnée par mergeConfigFrom avec
 * les défauts du package (vendor/sentry/sentry-laravel/src/.../ServiceProvider.php:133) - donc
 * aucune autre option Sentry n'est affectée.
 */
final class SentryUrlScrubber
{
    /**
     * Motifs regex (URL complète) dont le groupe capturant 1 est CONSERVÉ et le reste du
     * segment sensible remplacé par [jeton-filtre]. Chaque motif doit capturer le préfixe fixe
     * de l'URL dans le groupe 1 et laisser le jeton lui-même hors capture.
     *
     * @var array<int, string>
     */
    private const SENSITIVE_URL_PATTERNS = [
        // /decido/{poll}/gerer/{adminToken}(/fermer|/export.csv|/export.ics|/lien-court|/qr.png)
        '#(/decido/[^/?]+/gerer/)[^/?]+#',
    ];

    public static function scrubEvent(Event $event): Event
    {
        $request = $event->getRequest();

        if (isset($request['url']) && is_string($request['url'])) {
            $request['url'] = self::scrubUrl($request['url']);
            $event->setRequest($request);
        }

        return $event;
    }

    public static function scrubUrl(string $url): string
    {
        return preg_replace(self::SENSITIVE_URL_PATTERNS, '$1[jeton-filtre]', $url) ?? $url;
    }
}
