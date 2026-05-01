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

    public static function jaccardKeywords(string $a, string $b): float
    {
        $tokens = function (string $s): array {
            $clean = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', Str::ascii($s)));
            $stop = ['le','la','les','un','une','des','de','du','dans','pour','sur','et','ou','a','au','aux','en','par','avec','sans','ses','sa','son','ce','cette','que','qui','est','sont','the','a','an','to','of','in','on','for','and','or','is','are','was','were','be','by','with','from','as','it','its','this','that','these','those','can','will','has','have','had','new','newest','says','say','just','now','today','tomorrow'];
            return array_values(array_unique(array_diff(array_filter(explode(' ', $clean)), $stop)));
        };
        $tokA = $tokens($a);
        $tokB = $tokens($b);
        if (empty($tokA) || empty($tokB)) {
            return 0.0;
        }
        $inter = array_intersect($tokA, $tokB);
        $union = array_unique(array_merge($tokA, $tokB));
        return count($union) > 0 ? round(count($inter) / count($union), 3) : 0.0;
    }

    public static function extractKeyEntities(string $title): array
    {
        $tokens = preg_split('/\s+/', trim($title));
        if (!is_array($tokens)) {
            return [];
        }
        $knownAcronyms = ['IA', 'AI', 'API', 'GPT', 'LLM', 'ML', 'NLP', 'OCR', 'RAG', 'CPU', 'GPU', 'IoT', 'SaaS', 'SDK', '5G', '6G'];
        $stopEntities = ['the','this','that','these','those','from','with','for','and','or','but','is','are','was','were','be','been','been','being','have','has','had','do','does','did','can','could','will','would','should','may','might','must','shall','to','of','in','on','at','by','as','it','its','his','her','their','our','your','my','we','they','he','she','you','an','any','some','all','each','every','no','not','only','also','just','race','keep','running','wild','credit','cards','simple','models','evolution','encoders','more','most','less','least','very','really','quite','still','again','always','never','today','tomorrow','yesterday','now','then','here','there','where','when','what','who','why','how','introducing','new','launches','launch','announces','reveals','says','wants','puts','keeps','takes','gets','make','makes','made','goes','going','le','la','les','un','une','des','du','dans','pour','sur','et','ou','mais','sa','son','ses','cette','par','avec','sans','au','aux','si','que','qui','comment','pourquoi'];
        $entities = [];
        foreach ($tokens as $tok) {
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $tok);
            if ($clean === '') {
                continue;
            }
            $upper = mb_strtoupper($clean);
            $lower = mb_strtolower(Str::ascii($clean));
            if (in_array($upper, $knownAcronyms, true)) {
                $entities[] = $upper;
                continue;
            }
            if (mb_strlen($clean) < 4) {
                continue;
            }
            if (in_array($lower, $stopEntities, true)) {
                continue;
            }
            $firstChar = mb_substr($clean, 0, 1);
            $isCapitalized = $firstChar === mb_strtoupper($firstChar) && $firstChar !== mb_strtolower($firstChar);
            if ($isCapitalized) {
                $entities[] = $lower;
            }
        }
        return array_values(array_unique($entities));
    }

    public static function keyEntitiesIntersectionCount(string $a, string $b): int
    {
        $entA = self::extractKeyEntities($a);
        $entB = self::extractKeyEntities($b);
        return count(array_intersect($entA, $entB));
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

        $titleA = $newArticle['title'] ?? '';
        $titleB = $candidate['title'] ?? '';

        $withinWindow = true;
        if (!empty($newArticle['published_at']) && !empty($candidate['published_at'])) {
            $diff = abs(Carbon::parse($newArticle['published_at'])->timestamp - Carbon::parse($candidate['published_at'])->timestamp);
            $withinWindow = $diff < 86400;
        }

        if ($withinWindow && $titleA !== '' && $titleB !== '') {
            $jaccard = self::jaccardKeywords($titleA, $titleB);
            $entCount = self::keyEntitiesIntersectionCount($titleA, $titleB);

            if (self::titleSimilarity($titleA, $titleB) > 0.85) {
                $signals['title_fuzzy_high'] = true;
            }
            if ($jaccard >= 0.40) {
                $signals['jaccard_high'] = true;
            }
            if ($entCount >= 3 || ($entCount >= 2 && $jaccard >= 0.40)) {
                $signals['key_entities_match'] = true;
            }
        }

        if (($newArticle['source_language'] ?? null) === ($candidate['source_language'] ?? null)) {
            $signals['source_lang_match'] = true;
        }

        $coreKeys = ['normalized_url_match' => 1, 'canonical_match' => 1, 'title_fuzzy_high' => 1, 'jaccard_high' => 1, 'key_entities_match' => 1];
        $core = array_intersect_key($signals, $coreKeys);
        $isDup = isset($signals['normalized_url_match'])
            || isset($signals['canonical_match'])
            || isset($signals['key_entities_match'])
            || count($core) >= 2;

        $totalPossible = 6;
        $score = round(count($signals) / $totalPossible, 3);
        if (isset($signals['normalized_url_match'])) {
            $reason = 'normalized_url_match';
        } elseif (isset($signals['canonical_match'])) {
            $reason = 'canonical_match';
        } elseif (count($core) >= 2) {
            $reason = 'multi_core';
        } elseif (isset($signals['key_entities_match'])) {
            $reason = 'key_entities_match';
        } elseif (count($signals) === 1) {
            $reason = array_key_first($signals);
        } else {
            $reason = 'none';
        }

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
