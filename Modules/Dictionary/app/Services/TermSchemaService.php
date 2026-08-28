<?php

declare(strict_types=1);

namespace Modules\Dictionary\Services;

use Modules\Dictionary\Models\Term;

/**
 * Génère le bloc JSON-LD Schema.org @graph complet pour une page de terme glossaire.
 *
 * Inclus :
 *   - DefinedTermSet (le glossaire complet)
 *   - DefinedTerm (le terme courant)
 *   - Article ou WebPage (la page elle-même)
 *   - Person (l'auteur Stéphane Lapointe — signal EEAT)
 *   - BreadcrumbList (Accueil > Glossaire > Terme)
 *   - FAQPage (si le terme a des Q&A) — clé pour AI Overviews et LLM 2026
 *
 * 2026-05-26 #295 — créé pour respecter le standard 2026 SEO+AEO+GEO recommandé par sonar-pro.
 * DRY : un seul service, utilisé par la vue glossaire et potentiellement les bots/sitemap.
 */
final class TermSchemaService
{
    private const AUTHOR_NAME = 'Stéphane Lapointe';
    private const AUTHOR_LINKEDIN = 'https://www.linkedin.com/in/lapointestephane/';
    private const AUTHOR_JOB_TITLE = 'Fondateur de MEMORA solutions, formateur IA';
    private const SET_NAME = 'Glossaire Techno – La veille';
    private const SET_DESCRIPTION = "Glossaire vulgarisé de l'intelligence artificielle, contexte québécois, exemples concrets pour PME francophones.";

    /** URL canonique de l'auteur (env-aware via app_domain). */
    private static function authorUrl(): string
    {
        return app_domain('/auteur/stephane');
    }

    /** URL canonique du DefinedTermSet (env-aware via app_domain). */
    private static function setUrl(): string
    {
        return app_domain('/glossaire');
    }

    public static function buildGraph(Term $term): string
    {
        $url = route('dictionary.show', $term->slug);
        $description = trim(strip_tags($term->one_sentence_answer ?: $term->analogy ?: $term->definition ?: ''));
        $description = mb_substr($description, 0, 250);
        $imageUrl = $term->hero_image ? asset($term->hero_image) : null;
        $datePublished = optional($term->created_at)->toIso8601String();
        $dateModified = optional($term->updated_at)->toIso8601String();

        $graph = [];

        $graph[] = [
            '@type' => 'DefinedTermSet',
            '@id' => self::setUrl(),
            'name' => self::SET_NAME,
            'description' => self::SET_DESCRIPTION,
            'inLanguage' => 'fr-CA',
        ];

        $definedTerm = [
            '@type' => 'DefinedTerm',
            '@id' => $url.'#term',
            'name' => $term->name,
            'description' => $description,
            'url' => $url,
            'inDefinedTermSet' => self::setUrl(),
            'identifier' => $term->slug,
            'inLanguage' => 'fr-CA',
        ];
        // 2026-05-26 #301 : alternateName multivalué (acronym_full + aliases) — boost AEO/GEO
        $alternates = [];
        if (! empty($term->acronym_full)) {
            $alternates[] = trim((string) $term->acronym_full);
        }
        if (is_array($term->aliases)) {
            foreach ($term->aliases as $a) {
                if (! is_string($a)) continue;
                $a = trim($a);
                if (! $a) continue;
                if (mb_strtolower($a) === mb_strtolower((string) $term->name)) continue;
                if (! in_array($a, $alternates, true)) {
                    $alternates[] = $a;
                }
            }
        }
        if (! empty($alternates)) {
            $definedTerm['alternateName'] = count($alternates) === 1 ? $alternates[0] : $alternates;
        }

        // 2026-05-27 #304 : Schema.org broader/narrower — knowledge graph relations entre DefinedTerm.
        // Boost AEO/GEO (AI Overviews suit le graphe pour citer contextes parents/enfants).
        $broaderSlugs = is_array($term->broader_slugs) ? $term->broader_slugs : [];
        $narrowerSlugs = is_array($term->narrower_slugs) ? $term->narrower_slugs : [];
        $allRelated = array_values(array_unique(array_merge($broaderSlugs, $narrowerSlugs)));
        if (! empty($allRelated)) {
            // 2026-05-27 #304 : slug est Spatie Translatable JSON ({"fr_CA":"llm"}) — utiliser JSON_EXTRACT pour matcher
            $placeholders = implode(',', array_fill(0, count($allRelated), '?'));
            $relatedQuery = Term::query()
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.fr_CA')) IN ($placeholders)", $allRelated)
                ->where('is_published', 1)
                ->get(['id', 'slug', 'name'])
                ->keyBy(fn ($t) => (string) $t->slug);
            $toRefArray = function (array $slugs) use ($relatedQuery): array {
                $out = [];
                foreach ($slugs as $s) {
                    $rel = $relatedQuery->get((string) $s);
                    if (! $rel) {
                        continue;
                    }
                    $u = route('dictionary.show', $rel->slug);
                    $out[] = ['@type' => 'DefinedTerm', '@id' => $u.'#term', 'name' => (string) $rel->name, 'url' => $u];
                }
                return $out;
            };
            $broaderArr = $toRefArray($broaderSlugs);
            $narrowerArr = $toRefArray($narrowerSlugs);
            if (! empty($broaderArr)) {
                $definedTerm['broader'] = $broaderArr;
            }
            if (! empty($narrowerArr)) {
                $definedTerm['narrower'] = $narrowerArr;
            }
        }

        if ($imageUrl) {
            $definedTerm['image'] = $imageUrl;
        }
        if ($datePublished) {
            $definedTerm['dateCreated'] = $datePublished;
        }
        if ($dateModified) {
            $definedTerm['dateModified'] = $dateModified;
        }
        $graph[] = $definedTerm;

        $graph[] = [
            '@type' => 'Person',
            '@id' => self::authorUrl(),
            'name' => self::AUTHOR_NAME,
            'jobTitle' => self::AUTHOR_JOB_TITLE,
            'url' => self::authorUrl(),
            'sameAs' => [self::AUTHOR_LINKEDIN],
        ];

        $article = [
            '@type' => 'Article',
            '@id' => $url.'#article',
            'headline' => $term->name.' – Glossaire Techno',
            'description' => $description,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'author' => ['@id' => self::authorUrl()],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'La veille',
                'url' => app_domain(),
                'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo-email-white.png')],
            ],
            'inLanguage' => 'fr-CA',
        ];
        if ($datePublished) {
            $article['datePublished'] = $datePublished;
        }
        if ($dateModified) {
            $article['dateModified'] = $dateModified;
        }
        if ($imageUrl) {
            $article['image'] = $imageUrl;
        }
        $graph[] = $article;

        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => app_domain()],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Glossaire Techno', 'item' => self::setUrl()],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $term->name, 'item' => $url],
            ],
        ];

        $faq = $term->faq;
        if (is_array($faq) && ! empty($faq)) {
            $mainEntity = [];
            foreach ($faq as $qa) {
                if (! is_array($qa) || empty($qa['question']) || empty($qa['answer'])) {
                    continue;
                }
                $mainEntity[] = [
                    '@type' => 'Question',
                    'name' => (string) $qa['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags((string) $qa['answer']),
                    ],
                ];
            }
            if (! empty($mainEntity)) {
                $graph[] = [
                    '@type' => 'FAQPage',
                    '@id' => $url.'#faq',
                    'mainEntity' => $mainEntity,
                ];
            }
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}
