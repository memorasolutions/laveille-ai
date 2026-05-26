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
    private const AUTHOR_URL = 'https://laveille.ai/auteur/stephane';
    private const AUTHOR_LINKEDIN = 'https://www.linkedin.com/in/lapointestephane/';
    private const AUTHOR_JOB_TITLE = 'Fondateur de MEMORA solutions, formateur IA';
    private const SET_URL = 'https://laveille.ai/glossaire';
    private const SET_NAME = 'Glossaire IA — La veille';
    private const SET_DESCRIPTION = "Glossaire vulgarisé de l'intelligence artificielle, contexte québécois, exemples concrets pour PME francophones.";

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
            '@id' => self::SET_URL,
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
            'inDefinedTermSet' => self::SET_URL,
            'identifier' => $term->slug,
            'inLanguage' => 'fr-CA',
        ];
        if ($term->acronym_full) {
            $definedTerm['alternateName'] = $term->acronym_full;
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
            '@id' => self::AUTHOR_URL,
            'name' => self::AUTHOR_NAME,
            'jobTitle' => self::AUTHOR_JOB_TITLE,
            'url' => self::AUTHOR_URL,
            'sameAs' => [self::AUTHOR_LINKEDIN],
        ];

        $article = [
            '@type' => 'Article',
            '@id' => $url.'#article',
            'headline' => $term->name.' — Glossaire IA',
            'description' => $description,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'author' => ['@id' => self::AUTHOR_URL],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'La veille',
                'url' => 'https://laveille.ai',
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
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => 'https://laveille.ai'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Glossaire IA', 'item' => self::SET_URL],
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
