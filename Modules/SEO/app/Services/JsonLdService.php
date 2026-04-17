<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Services;

final class JsonLdService
{
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => config('app.url').'/images/og-image.png',
                'width' => 1200,
                'height' => 630,
            ],
            'description' => 'Plateforme communautaire de veille technologique en intelligence artificielle et education, orientee francophonie internationale et Quebec.',
            'founder' => [
                '@type' => 'Person',
                'name' => 'Stephane Lapointe',
                'url' => 'https://www.linkedin.com/in/lapointestephane/',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+1-418-800-6656',
                'contactType' => 'customer service',
                'availableLanguage' => 'French',
            ],
            'sameAs' => [
                'https://www.facebook.com/LaVeilleDeStef',
                'https://www.linkedin.com/in/lapointestephane/',
            ],
        ];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => config('app.url').'/blog?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function article(object $article): array
    {
        $data = [
            '@type' => 'Article',
            'headline' => $article->title,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'publisher' => self::organization(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current(),
            ],
        ];

        if ($article->cover_image ?? null) {
            $data['image'] = \Illuminate\Support\Facades\Storage::url($article->cover_image);
        }

        if ($article->user ?? null) {
            $data['author'] = [
                '@type' => 'Person',
                'name' => $article->user->name,
            ];
        }

        if ($article->meta_description ?? $article->excerpt ?? null) {
            $data['description'] = $article->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($article->excerpt ?? ''), 160);
        }

        return $data;
    }

    public static function webPage(object $page): array
    {
        return [
            '@type' => 'WebPage',
            'name' => $page->title,
            'url' => url()->current(),
            'description' => $page->meta_description ?? '',
            'dateModified' => $page->updated_at?->toIso8601String(),
        ];
    }

    public static function softwareApplication(object $tool): array
    {
        $description = \Illuminate\Support\Str::limit(strip_tags($tool->description ?? $tool->short_description ?? ''), 200);

        $schema = [
            '@type' => 'SoftwareApplication',
            'name' => $tool->name,
            'description' => $description,
            'url' => route('directory.show', $tool->slug),
            'applicationCategory' => $tool->categories->first()?->name ?? 'UtilitiesApplication',
            'operatingSystem' => 'Web',
            'offers' => [
                '@type' => 'Offer',
                'price' => in_array($tool->pricing, ['free', 'freemium', 'open_source']) ? '0' : '',
                'priceCurrency' => 'CAD',
                'availability' => 'https://schema.org/OnlineOnly',
            ],
        ];

        if ($tool->screenshot) {
            $schema['image'] = str_starts_with($tool->screenshot, 'http')
                ? $tool->screenshot
                : asset($tool->screenshot);
        }

        if ($tool->reviews?->count() > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($tool->reviews->avg('rating'), 1),
                'reviewCount' => $tool->reviews->count(),
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        if ($tool->launch_year) {
            $schema['datePublished'] = $tool->launch_year . '-01-01';
        }

        return $schema;
    }

    public static function newsArticle(object $article): array
    {
        return [
            '@type' => 'NewsArticle',
            'headline' => $article->seo_title ?? $article->title,
            'description' => $article->meta_description ?? \Illuminate\Support\Str::limit($article->summary ?? '', 155),
            'image' => $article->image_url ? url($article->image_url) : asset('images/og-image.png'),
            'datePublished' => $article->pub_date?->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => $article->source->name ?? config('app.name'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/favicon.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('news.show', $article),
            ],
        ];
    }

    public static function definedTerm(string $indexRouteName, string $setName, string $termName, string $description, ?string $termCode = null): array
    {
        return [
            '@type' => 'DefinedTerm',
            '@id' => url()->current(),
            'name' => $termName,
            'description' => $description,
            'inDefinedTermSet' => [
                '@type' => 'DefinedTermSet',
                '@id' => route($indexRouteName),
                'name' => $setName,
            ],
            'termCode' => $termCode ?? $termName,
        ];
    }

    public static function toolFaqPage(object $tool, $similarTools = null): array
    {
        $questions = [];

        if (! empty($tool->faq) && is_array($tool->faq)) {
            foreach ($tool->faq as $item) {
                $questions[] = ['question' => $item['question'] ?? '', 'answer' => $item['answer'] ?? ''];
            }
        } else {
            $name = $tool->name;

            $questions[] = [
                'question' => "Qu'est-ce que {$name} ?",
                'answer' => $tool->short_description ?? "{$name} est un outil d'intelligence artificielle disponible en ligne.",
            ];

            $pricingAnswer = match ($tool->pricing) {
                'free' => "{$name} est entièrement gratuit.",
                'freemium' => "{$name} propose une version gratuite avec des fonctionnalités premium payantes.",
                'open_source' => "{$name} est un logiciel open source et gratuit.",
                'paid' => "{$name} est un outil payant. Consultez le site officiel pour connaître les tarifs.",
                'contact' => "Les tarifs de {$name} sont disponibles sur demande. Contactez l'éditeur pour obtenir un devis.",
                default => "Consultez le site officiel de {$name} pour connaître les conditions tarifaires.",
            };
            $questions[] = ['question' => "{$name} est-il gratuit ?", 'answer' => $pricingAnswer];

            $altNames = $similarTools?->take(3)->pluck('name')->implode(', ');
            $questions[] = [
                'question' => "Quelles sont les alternatives à {$name} ?",
                'answer' => $altNames
                    ? "Parmi les alternatives à {$name}, on retrouve : {$altNames}."
                    : "Explorez notre répertoire pour découvrir des alternatives à {$name}.",
            ];

            $categories = $tool->categories->pluck('name')->implode(', ');
            $questions[] = [
                'question' => "À qui s'adresse {$name} ?",
                'answer' => $categories
                    ? "{$name} s'adresse aux utilisateurs intéressés par : {$categories}."
                    : "{$name} s'adresse à toute personne cherchant un outil d'intelligence artificielle performant.",
            ];

            if ($tool->launch_year) {
                $questions[] = [
                    'question' => "Depuis quand {$name} existe ?",
                    'answer' => "{$name} a été lancé en {$tool->launch_year}.",
                ];
            }
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $q) => [
                '@type' => 'Question',
                'name' => $q['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $q['answer'],
                ],
            ], $questions),
        ];
    }

    public static function breadcrumbs(array $items): array
    {
        $itemListElement = [];

        foreach ($items as $index => $item) {
            $element = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
            ];

            if (isset($item['url'])) {
                $element['item'] = $item['url'];
            }

            $itemListElement[] = $element;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        ];
    }

    public static function faqPage($faqs): array
    {
        $mainEntity = $faqs->map(function ($faq) {
            return [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->answer),
                ],
            ];
        })->all();

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];
    }

    /**
     * Rend un ou plusieurs schémas en balise JSON-LD.
     *
     * @param  array  ...$schemas  Chaque schéma est un array associatif
     */
    public static function render(array ...$schemas): string
    {
        $data = array_map(fn (array $schema) => array_merge(['@context' => 'https://schema.org'], $schema), $schemas);

        $json = json_encode(
            count($data) === 1 ? $data[0] : $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP
        );

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}
