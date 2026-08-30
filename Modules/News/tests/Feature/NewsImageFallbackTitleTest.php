<?php

declare(strict_types=1);

/**
 * Verrouille le titre baké dans la carte-titre de repli (NewsImageService::generateFallbackImage,
 * appelée par processFromUrl() et par RssFetcherService::run()) : seo_title est PRIORITAIRE sur
 * title, exactement comme partout ailleurs sur le site (show.blade.php, article-card,
 * searchableResultTitle()... lisent tous déjà `$article->seo_title ?? $article->title`).
 *
 * Bug mesuré en production le 2026-08-30 : processFromUrl() passait $article->title BRUT (le
 * titre de la source, souvent en anglais, jamais retypographié) à generateFallbackImage(), en
 * ignorant seo_title (le titre réellement publié). Sur 4493 fiches vivantes servant une image de
 * repli non curatée : 4491 bakaient un titre différent de celui affiché partout ailleurs sur la
 * même page, 1912 provenant d'une source non francophone (donc un titre anglais visible sur
 * l'image), 38 avec un tiret cadratin baké dans les pixels.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\NewsImageService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function niftSource(string $language = 'en'): NewsSource
{
    static $i = 0;
    $i++;

    return NewsSource::create([
        'name' => "Source test titre repli {$i}",
        'url' => "https://exemple.com/nift-source-{$i}",
        'language' => $language,
        'active' => true,
    ]);
}

function niftArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Raw source title {$i}",
        'guid' => "guid-nift-{$suffix}",
        'url' => "https://exemple.com/nift-{$suffix}",
        'description' => '',
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

it('privilégie seo_title sur title pour le titre de la carte-titre de repli', function (): void {
    $article = niftArticle(niftSource('en')->id, [
        'title' => 'OpenAI unveils new model',
        'seo_title' => 'OpenAI dévoile un nouveau modèle',
    ]);

    expect(NewsImageService::resolveFallbackTitle($article))
        ->toBe('OpenAI dévoile un nouveau modèle')
        ->not->toBe($article->title);
});

it('retombe sur title quand seo_title est absent (fiche jamais passée par la composition)', function (): void {
    $article = niftArticle(niftSource('fr')->id, [
        'title' => 'Titre brut seul, jamais composé',
        'seo_title' => null,
    ]);

    expect(NewsImageService::resolveFallbackTitle($article))->toBe('Titre brut seul, jamais composé');
});

it('retombe sur le défaut « La veille IA » quand aucun article n\'est fourni', function (): void {
    expect(NewsImageService::resolveFallbackTitle(null))->toBe('La veille IA');
});

it('accepte un défaut personnalisé quand aucun article n\'est fourni', function (): void {
    expect(NewsImageService::resolveFallbackTitle(null, 'Défaut personnalisé'))->toBe('Défaut personnalisé');
});
