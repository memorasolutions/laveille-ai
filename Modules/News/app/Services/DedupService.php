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

    /**
     * Similarité de Jaccard sur les mots significatifs de deux titres.
     *
     * Les mots vides vivent dans Modules/News/config/fusion.php ('stop_words') depuis le
     * 2026-08-13 : ils étaient codés en dur ici, et il y manquait « ai » et « ia ».
     */
    public static function jaccardKeywords(string $a, string $b): float
    {
        $tokens = function (string $s): array {
            $clean = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', Str::ascii($s)));
            $stop = (array) config('news.fusion.stop_words', []);
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

    /**
     * Entités nommées distinctives d'un titre (capitalisées, ou acronymes techniques connus).
     *
     * Les trois listes vivent dans Modules/News/config/fusion.php depuis le 2026-08-13.
     * « IA » et « AI » y figuraient parmi les acronymes connus, ce qui leur faisait contourner
     * à la fois le minimum de 4 caractères et le filtre des mots vides : ils sont désormais
     * dans 'generic_acronyms', reconnus mais jamais comptés.
     */
    public static function extractKeyEntities(string $title): array
    {
        $tokens = preg_split('/\s+/', trim($title));
        if (!is_array($tokens)) {
            return [];
        }
        $knownAcronyms = (array) config('news.fusion.known_acronyms', []);
        $genericAcronyms = (array) config('news.fusion.generic_acronyms', []);
        $stopEntities = (array) config('news.fusion.stop_entities', []);
        $entities = [];
        foreach ($tokens as $tok) {
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $tok);
            if ($clean === '') {
                continue;
            }
            $upper = mb_strtoupper($clean);
            $lower = mb_strtolower(Str::ascii($clean));
            // Acronyme trop générique pour identifier quoi que ce soit sur un site de veille en
            // intelligence artificielle : reconnu, mais jamais compté comme entité distinctive.
            if (in_array($upper, $genericAcronyms, true)) {
                continue;
            }
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

    /**
     * ACTION : clustering déterministe Actus 2.0 - « est-ce le même sujet couvert par des
     * sources différentes ? », question distincte de isLikelyDuplicate() (« est-ce une
     * republication quasi identique ? »). Réutilise jaccardKeywords()/keyEntitiesIntersectionCount()
     * tel quel (DRY), avec des seuils volontairement plus permissifs sur les entités mais toujours
     * conservateurs (voir design doc section 9, risque R1 : en cas de doute, singleton, jamais
     * l'inverse).
     * MCP: SELF (<5 lignes de logique nouvelle, réutilisation quasi totale)
     * RAISON: méthode distincte demandée explicitement (section 9) pour ne pas fusionner deux
     * seuils qui répondent à deux questions différentes.
     *
     * ACTION (observabilité Actus 2.0, 2026-08-11) : la clé 'entity_overlap' expose la valeur
     * NUMÉRIQUE du chevauchement d'entités (déjà calculée ci-dessous, jusqu'ici jetée - seul le
     * booléen 'key_entities_match' des signals survivait). Ajout de clé pur, signature publique
     * inchangée, aucun appelant existant ne fait de count()/itération stricte sur ce tableau
     * (tous accèdent par clé nommée - vérifié par grep sur les 4 appels du projet).
     * MCP: SELF (<5 lignes)
     * RAISON: permet à ArticleClusteringService de journaliser les quasi-regroupements (score
     * proche du seuil) sans recalculer l'intersection d'entités une deuxième fois (DRY).
     *
     * @return array{is_same_cluster: bool, score: float, reason: string, signals: array<string, bool>, entity_overlap: int}
     */
    public static function isSameStoryCluster(array $a, array $b, array &$signals = []): array
    {
        $signals = [];

        $titleA = $a['title'] ?? '';
        $titleB = $b['title'] ?? '';

        if ($titleA === '' || $titleB === '') {
            return ['is_same_cluster' => false, 'score' => 0.0, 'reason' => 'none', 'signals' => $signals, 'entity_overlap' => 0];
        }

        $jaccardThreshold = (float) config('news.fusion.jaccard_threshold', 0.30);
        $minEntityOverlap = (int) config('news.fusion.min_entity_overlap', 2);

        $jaccard = self::jaccardKeywords($titleA, $titleB);
        $entityOverlap = self::keyEntitiesIntersectionCount($titleA, $titleB);

        if ($jaccard >= $jaccardThreshold) {
            $signals['jaccard_high'] = true;
        }
        if ($entityOverlap >= $minEntityOverlap) {
            $signals['key_entities_match'] = true;
        }

        $isSameCluster = isset($signals['jaccard_high']) || isset($signals['key_entities_match']);

        $reason = 'none';
        if (isset($signals['jaccard_high']) && isset($signals['key_entities_match'])) {
            $reason = 'multi_core';
        } elseif (isset($signals['jaccard_high'])) {
            $reason = 'jaccard_high';
        } elseif (isset($signals['key_entities_match'])) {
            $reason = 'key_entities_match';
        }

        return [
            'is_same_cluster' => $isSameCluster,
            'score' => $jaccard,
            'reason' => $reason,
            'signals' => $signals,
            'entity_overlap' => $entityOverlap,
        ];
    }

    private static function stripAccents(string $s): string
    {
        return Str::ascii($s);
    }
}
