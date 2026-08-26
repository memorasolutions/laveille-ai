<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FaviconResolverService
{
    private const CACHE_TTL_DAYS = 30;

    private const FAIL_TTL_DAYS = 7;

    private const HEAD_TIMEOUT = 3;

    private const PROVIDERS = [
        'https://icons.duckduckgo.com/ip3/{domain}.ico',
        'https://icon.horse/icon/{domain}?size={size}',
        'https://www.google.com/s2/favicons?domain={domain}&sz={size}',
    ];

    /**
     * Domaines deja confies au job, pour ne pas empiler plusieurs fois le meme quand une page
     * affiche des dizaines de favicons.
     *
     * ATTENTION a la portee reelle : une statique vit le temps du PROCESSUS PHP, pas de la
     * requete. Sur l'hebergement mutualise actuel (PHP-FPM, un processus par requete) les deux
     * coincident, mais ce ne serait plus vrai avec Octane ou un worker de longue duree - le
     * dedoublonnage deviendrait alors permanent et bloquerait les rafraichissements suivants.
     *
     * @var array<string, bool>
     */
    private static array $dispatchesThisRequest = [];

    /**
     * Version NON BLOQUANTE de resolve(), destinee au RENDU.
     *
     * Mesure du 2026-08-26 : resolve() interroge jusqu'a 3 fournisseurs externes avec
     * 3 secondes de delai chacun. Appelee depuis une vue, elle faisait couter 4,4 a 10,6 s
     * la premiere visite d'une fiche d'outil, contre 0,5 s ensuite. Le rendu ne doit JAMAIS
     * attendre le reseau : cette methode lit le cache, rien d'autre, et confie le travail
     * reseau a ResolveFaviconJob.
     *
     * Une valeur perimee est retournee telle quelle : un favicon un peu vieux vaut mieux
     * qu'un trou dans la page, et le rafraichissement suit en arriere-plan.
     */
    public static function resolveCached(string $domain, int $size = 64): ?string
    {
        try {
            $domain = self::sanitizeDomain($domain);

            if ($domain === '' || $domain === null) {
                return null;
            }

            $cached = DB::table('favicon_cache')->where('domain', $domain)->first();

            if ($cached !== null && self::isCacheValid($cached)) {
                return $cached->resolved_url;
            }

            // Cache absent OU perime : dans les DEUX cas il faut declencher la resolution,
            // sinon un domaine jamais vu n'obtiendrait jamais de favicon.
            self::dispatchResolution($domain, $size);

            return $cached->resolved_url ?? null;
        } catch (\Throwable $e) {
            // Le rendu ne doit jamais tomber a cause d'un favicon.
            return null;
        }
    }

    private static function dispatchResolution(string $domain, int $size): void
    {
        if (isset(self::$dispatchesThisRequest[$domain])) {
            return;
        }

        self::$dispatchesThisRequest[$domain] = true;

        try {
            if (class_exists(\Modules\Core\Jobs\ResolveFaviconJob::class)) {
                \Modules\Core\Jobs\ResolveFaviconJob::dispatch($domain, $size);
            }
        } catch (\Throwable $e) {
            // File indisponible : on renonce silencieusement plutot que de casser la page.
        }
    }

    public static function resolve(string $domain, int $size = 64): ?string
    {
        try {
            $domain = self::sanitizeDomain($domain);

            if ($domain === '') {
                return null;
            }

            $cached = DB::table('favicon_cache')
                ->where('domain', $domain)
                ->first();

            if ($cached !== null && self::isCacheValid($cached)) {
                return $cached->resolved_url;
            }

            $resolvedUrl = self::probeProviders($domain, $size);

            $now = now();

            DB::table('favicon_cache')->updateOrInsert(
                ['domain' => $domain],
                [
                    'resolved_url' => $resolvedUrl,
                    'failed_count' => $resolvedUrl !== null
                        ? 0
                        : (($cached->failed_count ?? 0) + 1),
                    'checked_at'   => $now,
                    'updated_at'   => $now,
                    'created_at'   => $cached ? $cached->created_at : $now,
                ],
            );

            return $resolvedUrl;
        } catch (\Throwable $e) {
            Log::warning('[FaviconResolver] Échec pour le domaine « ' . $domain . ' » : ' . $e->getMessage());

            return null;
        }
    }

    public static function forgetDomain(string $domain): void
    {
        $domain = self::sanitizeDomain($domain);

        if ($domain === '') {
            return;
        }

        DB::table('favicon_cache')
            ->where('domain', $domain)
            ->delete();
    }

    private static function sanitizeDomain(string $domain): ?string
    {
        $domain = trim($domain);
        $domain = mb_strtolower($domain);
        $domain = preg_replace('/^www\./i', '', $domain);

        $host = parse_url('http://' . $domain, PHP_URL_HOST);

        if ($host === null || $host === false || $host === '') {
            return null;
        }

        return $host;
    }

    private static function isCacheValid(object $cached): bool
    {
        if ($cached->checked_at === null) {
            return false;
        }

        $checkedAt = \Carbon\Carbon::parse($cached->checked_at);

        $ttlDays = $cached->resolved_url !== null
            ? self::CACHE_TTL_DAYS
            : self::FAIL_TTL_DAYS;

        return $checkedAt->isAfter(now()->subDays($ttlDays));
    }

    private static function probeProviders(string $domain, int $size): ?string
    {
        foreach (self::PROVIDERS as $template) {
            $url = str_replace(
                ['{domain}', '{size}'],
                [$domain, (string) $size],
                $template,
            );

            try {
                $response = Http::timeout(self::HEAD_TIMEOUT)
                    ->connectTimeout(self::HEAD_TIMEOUT)
                    ->withOptions(['allow_redirects' => true])
                    ->head($url);

                if (! $response->successful()) {
                    continue;
                }

                $contentType = $response->header('Content-Type');

                if (self::isImageContentType($contentType)) {
                    return $url;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private static function isImageContentType(string $contentType): bool
    {
        $contentType = mb_strtolower(trim($contentType));

        $mime = explode(';', $contentType)[0] ?? '';
        $mime = trim($mime);

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $accepted = [
            'application/octet-stream',
            'text/plain',
        ];

        return in_array($mime, $accepted, true);
    }
}
