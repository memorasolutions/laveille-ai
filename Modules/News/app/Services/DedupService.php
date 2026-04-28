<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

final class DedupService
{
    public static function normalizeUrl(string $url, bool $stripWww = true): string
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return $url;
        }
        $scheme = strtolower($parsed['scheme']);
        $host = strtolower($parsed['host']);
        if ($stripWww && str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        $port = $parsed['port'] ?? null;
        if ($port === ($scheme === 'https' ? 443 : 80)) {
            $port = null;
        }
        $path = $parsed['path'] ?? '/';
        $path = ($path === '' || $path === '/') ? '/' : rtrim($path, '/');
        $query = '';
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            $tracking = ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid','ref','ref_src','mc_cid','mc_eid','_ga','igshid','yclid','_hsenc','_hsmi','hsctatracking','vero_id','vero_conv'];
            $filtered = array_filter($params, fn($k) => !in_array(strtolower((string)$k), $tracking, true), ARRAY_FILTER_USE_KEY);
            if (!empty($filtered)) {
                ksort($filtered);
                $query = '?' . http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
            }
        }
        return $scheme . '://' . $host . ($port ? ":{$port}" : '') . $path . $query;
    }

    public static function extractCanonical(string $html): ?string
    {
        if (preg_match('/<link[^>]+rel=["\']?canonical["\']?[^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<meta[^>]+property=["\']?og:url["\']?[^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function titleSimilarity(string $a, string $b): float
    {
        $clean = fn($s) => strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', Str::ascii($s)));
        $aClean = $clean($a);
        $bClean = $clean($b);
        similar_text($aClean, $bClean, $percent);
        return round($percent / 100, 3);
    }

    public static function isLikelyDuplicate(array $newArticle, array $candidate, array &$signals = []): array
    {
        $signals = [];

        $normA = self::normalizeUrl($newArticle['url'] ?? '');
        $normB = self::normalizeUrl($candidate['url'] ?? '');
        if ($normA && $normA === $normB) {
            $signals['normalized_url_match'] = true;
        }

        $canonA = $newArticle['canonical_url'] ?? null;
        $canonB = $candidate['canonical_url'] ?? null;
        if (!empty($canonA) && !empty($canonB) && self::normalizeUrl($canonA) === self::normalizeUrl($canonB)) {
            $signals['canonical_match'] = true;
        }

        $langMatch = ($newArticle['source_language'] ?? null) === ($candidate['source_language'] ?? null);
        if ($langMatch && !empty($newArticle['published_at']) && !empty($candidate['published_at'])) {
            $diff = abs(Carbon::parse($newArticle['published_at'])->timestamp - Carbon::parse($candidate['published_at'])->timestamp);
            if ($diff < 21600 && self::titleSimilarity($newArticle['title'] ?? '', $candidate['title'] ?? '') > 0.95) {
                $signals['title_fuzzy_high'] = true;
            }
        }

        if ($langMatch) {
            $signals['source_lang_match'] = true;
        }

        $core = array_intersect_key($signals, ['normalized_url_match' => 1, 'canonical_match' => 1, 'title_fuzzy_high' => 1]);
        $isDup = count($signals) >= 2 && count($core) >= 1;
        $score = round(count($signals) / 4, 3);
        $reason = count($signals) >= 2 ? 'multi_signal' : (count($signals) === 1 ? array_key_first($signals) : 'none');

        return [
            'is_duplicate' => $isDup,
            'score' => $score,
            'reason' => $reason,
            'signals' => $signals,
        ];
    }

    private static function stripAccents(string $s): string
    {
        return Str::ascii($s);
    }
}
