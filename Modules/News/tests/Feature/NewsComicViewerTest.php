<?php

declare(strict_types=1);

/**
 * Standard « visionneur de BD » sur la fiche d'actualité.
 *
 * Détection par convention (présence de public/bd/{slug}/manifest.json) : la fiche
 * d'un article avec planche rend le déclencheur « Lire la BD » ; sans planche, rien.
 * 100 % additif et gaté par ComicLibrary::hasComic — réutilisation pure du glossaire.
 *
 * @project laveille.ai — MEMORA solutions
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Dictionary\Support\ComicLibrary;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Crée une actualité publiée avec un slug donné. */
function makeComicArticle(int $sourceId, string $slug): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Test '.$slug,
        'guid' => 'guid-'.$slug,
        'url' => 'https://exemple.com/'.$slug,
        'description' => 'Description de test pour '.$slug,
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

beforeEach(function () {
    Http::fake();
    $this->source = NewsSource::create(['name' => 'TestSource', 'url' => 'https://exemple.com', 'language' => 'fr']);
});

it('rend le visionneur de BD sur une actualité possédant un manifest', function () {
    // Slug de la 1re BD actualité réellement déposée (public/bd/{slug}/manifest.json).
    $slug = 'read-this-before-you-vibe-code-another-app';
    expect(ComicLibrary::hasComic($slug))->toBeTrue();

    $article = makeComicArticle($this->source->id, $slug);

    $this->get(route('news.show', $article))
        ->assertOk()
        ->assertSee('Lire la BD')
        ->assertSee('cbd-trigger', false);
});

it('ne rend aucun visionneur sur une actualité sans manifest', function () {
    $slug = 'actualite-sans-bande-dessinee';
    expect(ComicLibrary::hasComic($slug))->toBeFalse();

    $article = makeComicArticle($this->source->id, $slug);

    $this->get(route('news.show', $article))
        ->assertOk()
        ->assertDontSee('Lire la BD')
        ->assertDontSee('cbd-trigger', false);
});
