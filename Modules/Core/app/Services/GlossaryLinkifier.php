<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Auto-linking glossaire/acronymes (Memora #141, 2026-05-05).
 *
 * Détecte les termes du Dictionary + Acronyms dans un HTML et insère :
 *  - <a class="glossary-link" href="/glossaire/X" data-tooltip="...">terme</a>
 *  - first-occurrence-only par appel
 *  - skip h1-h6, code, pre, a, abbr, blockquote, dfn, label
 *  - skip self-linking sur la page du terme lui-même
 *
 * Performance : Trie virtuel via regex compilée (sorted by length desc).
 * Cache 1h via Model events (Term + Acronym).
 *
 * Usage:
 *   {!! GlossaryLinkifier::linkify($article->description) !!}
 *   $matched = GlossaryLinkifier::extractMatchedTerms($html); // pour Schema.org
 */
class GlossaryLinkifier
{
    public const CACHE_KEY = 'glossary.terms.';
    public const CACHE_TTL = 3600; // 1h
    public const MIN_LENGTH = 4; // skip ≤3 chars (faux positifs IA, ML, AI)
    public const MAX_LINKS_PER_PAGE = 12;

    /**
     * 2026-05-05 #141 b : tracking cumulatif inter-appels.
     * Une page peut appeler @glossarize() plusieurs fois (hook, key_points, why_important, etc.).
     * On veut first-occurrence GLOBAL et accumulation des matched terms pour Schema.org.
     * Reset automatique au prochain cycle de requête (singleton naturel Laravel).
     */
    protected static array $matchedThisRequest = [];
    protected static array $seenThisRequest = [];
    protected static int $linkCountThisRequest = 0;

    /**
     * Auto-linkify un HTML : injecte des liens vers Dictionary/Acronyms.
     */
    public static function linkify(?string $html, array $options = []): string
    {
        if (empty($html) || ! is_string($html)) {
            return (string) $html;
        }

        $skipSlug = $options['skip_slug'] ?? null;
        $maxLinks = $options['max_links'] ?? self::MAX_LINKS_PER_PAGE;

        $terms = self::loadTerms();
        if (empty($terms)) {
            return $html;
        }

        // Tracking cumulatif inter-appels (par requête HTTP)

        try {
            $dom = new \DOMDocument;
            // Charset trick: force UTF-8 + suppress HTML5 warnings
            libxml_use_internal_errors(true);
            $wrapped = '<?xml encoding="UTF-8"?><div id="glx-root">'.$html.'</div>';
            $dom->loadHTML($wrapped, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
        } catch (\Throwable $e) {
            Log::warning('GlossaryLinkifier::linkify - DOM parse fail', ['msg' => $e->getMessage()]);
            return $html;
        }

        $root = $dom->getElementById('glx-root');
        if (! $root) {
            return $html;
        }

        self::walkAndReplace($dom, $root, $terms, self::$seenThisRequest, self::$linkCountThisRequest, $maxLinks, $skipSlug);

        // Extract inner HTML from glx-root wrapper
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }

    /**
     * Récupère les termes matchés pendant le dernier linkify() appel.
     * Utilisé par Schema.org JSON-LD pour générer DefinedTermSet.
     *
     * @return array<int, array{name:string, slug:string, definition:string, type:string, url:string}>
     */
    public static function getLastMatchedTerms(): array
    {
        return array_values(self::$matchedThisRequest);
    }

    /**
     * Reset state (utile pour Octane / jobs queue / tests).
     */
    public static function resetState(): void
    {
        self::$matchedThisRequest = [];
        self::$seenThisRequest = [];
        self::$linkCountThisRequest = 0;
    }

    /**
     * Charge les termes Dictionary + Acronyms depuis cache (1h TTL).
     * Sorted by length DESC pour matcher les expressions longues en premier.
     */
    public static function loadTerms(): array
    {
        $locale = app()->getLocale() ?: 'fr_CA';

        return Cache::remember(self::CACHE_KEY.$locale, self::CACHE_TTL, function () use ($locale) {
            $terms = [];

            // Dictionary
            if (class_exists(\Modules\Dictionary\Models\Term::class)) {
                try {
                    \Modules\Dictionary\Models\Term::published()
                        ->select(['id', 'name', 'slug', 'definition', 'type'])
                        ->get()
                        ->each(function ($t) use (&$terms, $locale) {
                            $name = $t->getTranslation('name', $locale, false) ?: $t->name;
                            $slug = $t->getTranslation('slug', $locale, false) ?: $t->slug;
                            $def = $t->getTranslation('definition', $locale, false) ?: $t->definition;
                            if (! $name || ! $slug || mb_strlen($name) < self::MIN_LENGTH) return;
                            $terms[] = [
                                'name' => $name,
                                'slug' => $slug,
                                'definition' => Str::limit(strip_tags((string) $def), 180),
                                'type' => 'glossary',
                                'url' => '/glossaire/'.$slug,
                            ];
                        });
                } catch (\Throwable $e) {
                    Log::warning('GlossaryLinkifier - Term load fail', ['e' => $e->getMessage()]);
                }
            }

            // Acronyms (matche acronym ET full_name)
            if (class_exists(\Modules\Acronyms\Models\Acronym::class)) {
                try {
                    \Modules\Acronyms\Models\Acronym::published()
                        ->select(['id', 'acronym', 'full_name', 'slug', 'description'])
                        ->get()
                        ->each(function ($a) use (&$terms, $locale) {
                            $acro = $a->getTranslation('acronym', $locale, false) ?: $a->acronym;
                            $full = $a->getTranslation('full_name', $locale, false) ?: $a->full_name;
                            $slug = $a->getTranslation('slug', $locale, false) ?: $a->slug;
                            $desc = $a->getTranslation('description', $locale, false) ?: $a->description;
                            if (! $slug) return;
                            $url = '/acronymes-education/'.$slug;
                            $shortDesc = Str::limit(strip_tags((string) $desc), 180);
                            // Acronyme exact (ex "OBVIA") : strict casse + min 2 chars
                            if ($acro && mb_strlen($acro) >= 2) {
                                $terms[] = [
                                    'name' => $acro,
                                    'slug' => $slug,
                                    'definition' => $full ? "{$full} : {$shortDesc}" : $shortDesc,
                                    'type' => 'acronym',
                                    'url' => $url,
                                    'case_sensitive' => true,
                                ];
                            }
                            // Forme longue (ex "Observatoire de l'IA et du numérique")
                            if ($full && mb_strlen($full) >= self::MIN_LENGTH) {
                                $terms[] = [
                                    'name' => $full,
                                    'slug' => $slug,
                                    'definition' => $shortDesc,
                                    'type' => 'acronym_full',
                                    'url' => $url,
                                ];
                            }
                        });
                } catch (\Throwable $e) {
                    Log::warning('GlossaryLinkifier - Acronym load fail', ['e' => $e->getMessage()]);
                }
            }

            // Sort par longueur DESC (matche les expressions longues en priorité)
            usort($terms, fn ($a, $b) => mb_strlen($b['name']) <=> mb_strlen($a['name']));

            return $terms;
        });
    }

    /**
     * Invalidation cache (appelée par Model events sur Term + Acronym).
     */
    public static function flushCache(): void
    {
        foreach (['fr_CA', 'fr', 'en', 'en_CA'] as $loc) {
            Cache::forget(self::CACHE_KEY.$loc);
        }
    }

    /**
     * Walk récursif DOM + remplacement text nodes hors zones interdites.
     */
    protected static function walkAndReplace(\DOMDocument $dom, \DOMNode $node, array $terms, array &$seen, int &$linkCount, int $maxLinks, ?string $skipSlug): void
    {
        if ($linkCount >= $maxLinks) return;

        // Skip zones interdites
        $skipTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'code', 'pre', 'abbr', 'blockquote', 'dfn', 'label', 'script', 'style', 'kbd', 'samp', 'var'];
        if ($node->nodeType === XML_ELEMENT_NODE && in_array(strtolower($node->nodeName), $skipTags, true)) {
            return;
        }

        // Cloner les enfants car on va modifier la structure pendant l'iteration
        $children = [];
        foreach ($node->childNodes as $c) $children[] = $c;

        foreach ($children as $child) {
            if ($linkCount >= $maxLinks) break;

            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->nodeValue;
                if (! $text || mb_strlen(trim($text)) < self::MIN_LENGTH) continue;

                $replaced = self::matchInText($dom, $text, $terms, $seen, $linkCount, $maxLinks, $skipSlug);
                if ($replaced !== null) {
                    // Replace text node par fragment (mix text + <a>)
                    $parent = $child->parentNode;
                    foreach ($replaced as $newNode) {
                        $parent->insertBefore($newNode, $child);
                    }
                    $parent->removeChild($child);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                self::walkAndReplace($dom, $child, $terms, $seen, $linkCount, $maxLinks, $skipSlug);
            }
        }
    }

    /**
     * Cherche dans un text le premier match non-encore-vu, retourne fragment ou null.
     *
     * @return array<\DOMNode>|null
     */
    protected static function matchInText(\DOMDocument $dom, string $text, array $terms, array &$seen, int &$linkCount, int $maxLinks, ?string $skipSlug): ?array
    {
        foreach ($terms as $term) {
            if ($linkCount >= $maxLinks) return null;
            if (isset($seen[$term['slug'].'|'.$term['type']])) continue;
            if ($skipSlug && $term['slug'] === $skipSlug) continue;

            $name = $term['name'];
            $caseSensitive = $term['case_sensitive'] ?? false;

            // Word boundary match avec accents UTF-8
            $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($name, '/').'(?![\p{L}\p{N}])/u';
            if (! $caseSensitive) $pattern .= 'i';

            if (! preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) continue;

            $matchedText = $m[0][0];
            $offset = $m[0][1];
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($matchedText));

            // Build fragment
            $fragment = [];
            if ($before !== '') $fragment[] = $dom->createTextNode($before);

            $a = $dom->createElement('a');
            $a->appendChild($dom->createTextNode($matchedText));
            $a->setAttribute('href', $term['url']);
            $a->setAttribute('class', 'glossary-link');
            $a->setAttribute('data-tooltip', $term['definition']);
            $a->setAttribute('title', $term['definition']);
            $a->setAttribute('aria-label', $name.' — '.($term['type'] === 'acronym' || $term['type'] === 'acronym_full' ? 'voir acronyme (nouvel onglet)' : 'voir glossaire (nouvel onglet)'));
            // 2026-05-05 #141 : ouvre dans nouvel onglet pour preserver la lecture en cours
            $a->setAttribute('target', '_blank');
            $a->setAttribute('rel', 'noopener noreferrer');
            $fragment[] = $a;

            if ($after !== '') $fragment[] = $dom->createTextNode($after);

            // Mark seen (cumulatif inter-appels via static)
            $seen[$term['slug'].'|'.$term['type']] = true;
            self::$matchedThisRequest[$term['slug']] = $term;
            $linkCount++;

            return $fragment;
        }

        return null;
    }
}
