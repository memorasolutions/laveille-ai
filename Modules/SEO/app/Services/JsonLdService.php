<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
            'logo' => config('app.url') . '/logo.png',
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
                'target' => config('app.url') . '/search?q={search_term_string}',
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

        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
