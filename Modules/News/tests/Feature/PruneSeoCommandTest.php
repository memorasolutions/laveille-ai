<?php

declare(strict_types=1);

/**
 * Tests de la commande d'élagage SEO réversible des actualités (news:prune-seo).
 *
 * @project laveille.ai — MEMORA solutions
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Crée un article persisté avec des valeurs SEO contrôlées. */
function makeArticle(int $sourceId, string $slug, $pubDate, int $views, string $status = 'index', bool $published = true): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Test '.$slug,
        'guid' => 'guid-'.$slug,
        'url' => 'https://exemple.com/'.$slug,
        'description' => 'Description de test pour '.$slug,
        'slug' => $slug,
        'pub_date' => $pubDate,
        'is_published' => $published,
        'views_count' => $views,
        'seo_status' => $status,
    ]);
}

beforeEach(function () {
    Http::fake(); // neutralise tout appel réseau (IndexNow).
    config(['news.seo_prune' => ['enabled' => true, 'min_age_months' => 12, 'max_views' => 30, 'gone' => ['enabled' => false, 'age_months' => 24, 'max_views' => 5]]]);
    $this->source = NewsSource::create(['name' => 'TestSource', 'url' => 'https://exemple.com', 'language' => 'fr']);
});

it('passe en noindex une vieille actualité peu vue, garde les performantes et les récentes', function () {
    $old = makeArticle($this->source->id, 'vieux-peu-vu', now()->subMonths(13), 10);
    $oldPopular = makeArticle($this->source->id, 'vieux-populaire', now()->subMonths(13), 100);
    $recent = makeArticle($this->source->id, 'recent-peu-vu', now()->subDays(5), 2);

    $this->artisan('news:prune-seo')->assertSuccessful();

    expect($old->fresh()->seo_status)->toBe('noindex');
    expect($oldPopular->fresh()->seo_status)->toBe('index');
    expect($recent->fresh()->seo_status)->toBe('index');
});

it('réindexe (auto-healing) une actualité noindex redevenue performante', function () {
    $recovered = makeArticle($this->source->id, 'noindex-regain', now()->subMonths(13), 100, 'noindex');

    $this->artisan('news:prune-seo')->assertSuccessful();

    expect($recovered->fresh()->seo_status)->toBe('index');
});

it('--dry-run ne modifie rien', function () {
    $old = makeArticle($this->source->id, 'dry-vieux', now()->subMonths(13), 1);

    $this->artisan('news:prune-seo --dry-run')->assertSuccessful();

    expect($old->fresh()->seo_status)->toBe('index');
});

it('--reset remet tout à index', function () {
    $a = makeArticle($this->source->id, 'reset-a', now()->subMonths(13), 1, 'noindex');
    $b = makeArticle($this->source->id, 'reset-b', now()->subMonths(30), 0, 'gone');

    $this->artisan('news:prune-seo --reset')->assertSuccessful();

    expect($a->fresh()->seo_status)->toBe('index');
    expect($b->fresh()->seo_status)->toBe('index');
});

it('ne fait rien quand l\'élagage est désactivé', function () {
    config(['news.seo_prune.enabled' => false]);
    $old = makeArticle($this->source->id, 'disabled-vieux', now()->subMonths(13), 1);

    $this->artisan('news:prune-seo')->assertSuccessful();

    expect($old->fresh()->seo_status)->toBe('index');
});
