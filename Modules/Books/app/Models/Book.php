<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Books\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;

/**
 * Modèle Book — squelette de la future bibliothèque de livres (/livres).
 *
 * Pattern calqué sur Modules\Dictionary\Models\Term : HasPublishedState pour
 * le scope is_published, Searchable pour l'intégration à la recherche globale
 * cross-module (voir SearchRegistry côté module Search).
 */
class Book extends Model implements Searchable
{
    use HasPublishedState;

    protected $table = 'books';

    protected $fillable = [
        'title',
        'subtitle',
        'slug',
        'series_slug',
        'series_position',
        'genre',
        'target_audience',
        'author_bio_short',
        'one_sentence_answer',
        'benefits',
        'excerpt',
        'toc_summary',
        'faq',
        'isbn_paperback',
        'asin_kindle',
        'price_paperback',
        'price_kindle',
        'amazon_url_paperback',
        'amazon_url_kindle',
        'cover_image',
        'date_published',
        'is_under_construction',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'benefits' => 'array',
        'toc_summary' => 'array',
        'faq' => 'array',
        'price_paperback' => 'decimal:2',
        'price_kindle' => 'decimal:2',
        'date_published' => 'date',
        'is_under_construction' => 'boolean',
        'is_published' => 'boolean',
        'series_position' => 'integer',
        'sort_order' => 'integer',
    ];

    // 2026-07-08 : scopePublished mutualisé via HasPublishedState (DRY Core).

    public static function searchableFields(): array
    {
        return ['title', 'subtitle', 'excerpt', 'author_bio_short'];
    }

    public static function searchSectionKey(): string
    {
        return 'livres';
    }

    public static function searchSectionLabel(): string
    {
        return __('Livres');
    }

    public static function searchSectionIcon(): string
    {
        return '📖';
    }

    public static function searchPriority(): int
    {
        return 60;
    }

    public function searchableResultTitle(): string
    {
        return $this->title;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->excerpt ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('books.show', $this->slug);
    }

    /**
     * Pages de l'extrait « feuilletable » (x-fronttheme::flip-reader), scannées
     * dynamiquement dans public/images/livres-extraits/{slug}/page-NN.jpg -
     * jamais de valeur en dur : un livre sans dossier retourne simplement [].
     *
     * Inclut le LQIP (Low Quality Image Placeholder, ~40px, quelques Ko) associé
     * à chaque page quand il existe (page-NN-lqip.jpg, généré hors-ligne via
     * ImageMagick) : le composant flip-reader l'affiche flouté pendant le
     * chargement de l'image pleine résolution. Défensif : si le LQIP n'a pas
     * encore été généré pour une page, la clé vaut simplement null - le
     * squelette shimmer du composant suffit alors seul.
     *
     * @return array<int, array{image: string, lqip: ?string, alt: string, width: int, height: int}>
     */
    public function excerptPages(): array
    {
        if (blank($this->slug)) {
            return [];
        }

        $dir = public_path('images/livres-extraits/'.$this->slug);

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/page-*.jpg') ?: [];
        // Exclure les LQIP eux-mêmes du glob (page-NN-lqip.jpg matche aussi page-*.jpg).
        $files = array_values(array_filter($files, fn (string $f): bool => ! str_ends_with($f, '-lqip.jpg')));

        if (! $files) {
            return [];
        }

        natsort($files);
        $files = array_values($files);

        [$width, $height] = @getimagesize($files[0]) ?: [0, 0];
        $width = (int) $width;
        $height = (int) $height;

        $pages = [];

        foreach ($files as $index => $file) {
            $page = $index + 1;
            $lqipPath = $dir.'/'.pathinfo($file, PATHINFO_FILENAME).'-lqip.jpg';

            $pages[] = [
                'image' => asset('images/livres-extraits/'.$this->slug.'/'.basename($file)),
                'lqip' => is_file($lqipPath)
                    ? asset('images/livres-extraits/'.$this->slug.'/'.basename($lqipPath))
                    : null,
                'alt' => trim(__('Page :page de l\'extrait de :title', [
                    'page' => $page,
                    'title' => (string) $this->title,
                ])),
                'width' => $width,
                'height' => $height,
            ];
        }

        return $pages;
    }
}
