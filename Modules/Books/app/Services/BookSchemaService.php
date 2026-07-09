<?php

declare(strict_types=1);

namespace Modules\Books\Services;

use Modules\Books\Models\Book;

/**
 * Génère le bloc JSON-LD Schema.org @graph complet pour une page de livre (/livres/{slug}).
 *
 * Inclus :
 *   - Book (le livre courant — author Person + worksFor Organization, isbn, bookFormat,
 *     datePublished, image, description, offers[] par format disponible)
 *   - BreadcrumbList (Accueil > Livres > Titre)
 *   - FAQPage (si le livre a des Q&A) — clé pour AI Overviews et LLM 2026
 *   - isPartOf (si series_slug rempli) : @id synthétique de la série
 *
 * Pattern calqué sur Modules\Dictionary\Services\TermSchemaService::buildGraph(), IRIs
 * bookFormat/offers repris du composant <x-fronttheme::book-promo> déjà en prod.
 *
 * 2026-07-08 : squelette créé pour Modules\Books (schéma seul, pas de données).
 */
final class BookSchemaService
{
    private const AUTHOR_NAME = 'Stéphane Lapointe';
    private const AUTHOR_LINKEDIN = 'https://www.linkedin.com/in/lapointestephane/';
    private const AUTHOR_JOB_TITLE = 'Fondateur de MEMORA solutions, formateur IA';
    private const PUBLISHER_NAME = 'MEMORA solutions';
    private const INDEX_LABEL = 'Livres';

    /** URL canonique de l'auteur (env-aware via app_domain). */
    private static function authorUrl(): string
    {
        return app_domain('/auteur/stephane');
    }

    /** URL canonique de l'index de la bibliothèque (env-aware via app_domain). */
    private static function indexUrl(): string
    {
        return app_domain('/livres');
    }

    public static function buildGraph(Book $book): array
    {
        $url = route('books.show', $book->slug);
        $description = trim(strip_tags($book->one_sentence_answer ?: $book->excerpt ?: ''));
        $description = mb_substr($description, 0, 250);
        $imageUrl = $book->cover_image ? asset($book->cover_image) : null;
        $datePublished = optional($book->date_published)->toDateString() ?: optional($book->created_at)->toIso8601String();
        $dateModified = optional($book->updated_at)->toIso8601String();

        $graph = [];

        $bookNode = [
            '@type' => 'Book',
            '@id' => $url.'#book',
            'name' => $book->title,
            'url' => $url,
            'inLanguage' => 'fr-CA',
            'author' => [
                '@type' => 'Person',
                '@id' => self::authorUrl(),
                'name' => self::AUTHOR_NAME,
                'jobTitle' => self::AUTHOR_JOB_TITLE,
                'url' => self::authorUrl(),
                'sameAs' => [self::AUTHOR_LINKEDIN],
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => self::PUBLISHER_NAME,
                    'url' => app_domain(),
                ],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => self::PUBLISHER_NAME,
                'url' => app_domain(),
            ],
        ];

        if (! empty($book->subtitle)) {
            $bookNode['alternativeHeadline'] = $book->subtitle;
        }
        if ($description !== '') {
            $bookNode['description'] = $description;
        }
        if ($imageUrl) {
            $bookNode['image'] = $imageUrl;
        }
        if ($datePublished) {
            $bookNode['datePublished'] = $datePublished;
        }
        if ($dateModified) {
            $bookNode['dateModified'] = $dateModified;
        }
        if (! empty($book->isbn_paperback)) {
            $bookNode['isbn'] = $book->isbn_paperback;
        }
        if (! empty($book->genre)) {
            $bookNode['genre'] = $book->genre;
        }
        if (! empty($book->target_audience)) {
            $bookNode['audience'] = [
                '@type' => 'Audience',
                'audienceType' => $book->target_audience,
            ];
        }

        // bookFormat : IRI officielle schema.org selon les formats effectivement disponibles.
        // Un seul livre peut exister en broché ET Kindle -> bookFormat multivalué (array).
        $formats = [];
        if (! empty($book->isbn_paperback) || ! empty($book->amazon_url_paperback)) {
            $formats[] = 'https://schema.org/Paperback';
        }
        if (! empty($book->asin_kindle) || ! empty($book->amazon_url_kindle)) {
            $formats[] = 'https://schema.org/EBook';
        }
        if (! empty($formats)) {
            $bookNode['bookFormat'] = count($formats) === 1 ? $formats[0] : $formats;
        }

        // offers[] : un Offer par format rempli (prix + URL + disponibilité).
        $offers = [];
        if (! empty($book->amazon_url_paperback)) {
            $offer = [
                '@type' => 'Offer',
                'url' => $book->amazon_url_paperback,
                'availability' => 'https://schema.org/InStock',
                'priceCurrency' => 'CAD',
                'seller' => ['@type' => 'Organization', 'name' => 'Amazon'],
            ];
            if ($book->price_paperback !== null) {
                $offer['price'] = (string) $book->price_paperback;
            }
            $offers[] = $offer;
        }
        if (! empty($book->amazon_url_kindle)) {
            $offer = [
                '@type' => 'Offer',
                'url' => $book->amazon_url_kindle,
                'availability' => 'https://schema.org/InStock',
                'priceCurrency' => 'CAD',
                'seller' => ['@type' => 'Organization', 'name' => 'Amazon'],
            ];
            if ($book->price_kindle !== null) {
                $offer['price'] = (string) $book->price_kindle;
            }
            $offers[] = $offer;
        }
        if (! empty($offers)) {
            $bookNode['offers'] = $offers;
        }

        // isPartOf : série (si le livre appartient à une série) — @id synthétique.
        if (! empty($book->series_slug)) {
            $isPartOf = [
                '@type' => 'BookSeries',
                '@id' => app_domain('/livres/serie/'.$book->series_slug),
                'url' => app_domain('/livres/serie/'.$book->series_slug),
            ];
            if ($book->series_position !== null) {
                $bookNode['position'] = $book->series_position;
            }
            $bookNode['isPartOf'] = $isPartOf;
        }

        $graph[] = $bookNode;

        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => app_domain()],
                ['@type' => 'ListItem', 'position' => 2, 'name' => self::INDEX_LABEL, 'item' => self::indexUrl()],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $book->title, 'item' => $url],
            ],
        ];

        $faq = $book->faq;
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

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }
}
