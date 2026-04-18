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

    public static function resolve(string $domain, int $size = 64): ?string
    {
        try {
            $domain = self::sanitizeDomain($domain);

            if ($domain === '' || $domain === null) {
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
            Log::warning('[FaviconResolver] Échec pour le domaine « ' . ($domain ?? '?') . ' » : ' . $e->getMessage());

            return null;
        }
    }

    public static function forgetDomain(string $domain): void
    {
        $domain = self::sanitizeDomain($domain);

        if ($domain === '' || $domain === null) {
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

                $contentType = $response->header('Content-Type') ?? '';

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
