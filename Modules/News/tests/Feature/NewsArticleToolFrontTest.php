<?php

declare(strict_types=1);

/**
 * Tests front-end Phase 3 : onglet Actualités sur fiche outil + bloc Outils sur actualité.
 *
 * Stratégie :
 *  - Onglet Directory : tests sur la vue Blade (lecture fichier, comme les tests Directory
 *    existants) pour contourner l'incompatibilité FIELD() MySQL/SQLite + un test de
 *    relation modèle vérifiant le comptage conditionnel.
 *  - Page actualité : tests HTTP sur news.show (pas de FIELD() dans ce contrôleur).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ────────────────────────────────────────────────────────────

function natFrontSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source front test',
        'url' => 'https://nat-front.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function natFrontArticle(int $sourceId, string $suffix = 'F', bool $published = true): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article front {$suffix}",
        'guid' => "guid-nat-front-{$suffix}",
        'url' => "https://exemple.com/nat-front-{$suffix}",
        'description' => "Description de test pour front {$suffix}",
        'slug' => "article-front-" . strtolower($suffix),
        'pub_date' => now()->subDay(),
        'is_published' => $published,
        'seo_status' => 'index',
    ]);
}

function natFrontTool(string $suffix = 'F'): Tool
{
    $name = "Outil front {$suffix}";
    $slug = "outil-front-" . strtolower($suffix);

    // Tableau associatif (PAS json_encode) pour que Spatie appelle setTranslations() correctement.
    return Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => $name, 'en' => $name],
        'slug' => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

// ── Tests de la vue Directory show (lecture fichier, compatible SQLite) ───────

it('la vue Directory show contient l\'onglet news dans la whitelist Alpine', function () {
    $content = file_get_contents(base_path('Modules/Directory/resources/views/public/show.blade.php'));
    expect($content)->toContain("'news'");
    expect($content)->toContain("'news'].includes(window.location.hash");
});

it('la vue Directory show contient le bouton onglet Actualités conditionnel', function () {
    $content = file_get_contents(base_path('Modules/Directory/resources/views/public/show.blade.php'));
    expect($content)->toContain("tab==='news'");
    expect($content)->toContain("\$toolNewsArticles->isNotEmpty()");
    expect($content)->toContain("Actualités");
});

it('la vue Directory show contient le panneau news avec les cartes actualités', function () {
    $content = file_get_contents(base_path('Modules/Directory/resources/views/public/show.blade.php'));
    expect($content)->toContain("x-show=\"tab==='news'\"");
    expect($content)->toContain('news.show');
    expect($content)->toContain('pub_date');
});

// ── Tests de relation (modèle) : logique de comptage ─────────────────────────

it('un outil sans actualités liées renvoie 0 actualités publiées', function () {
    $tool = natFrontTool('ZERO');
    expect($tool->newsArticles()->published()->count())->toBe(0);
});

it('un outil avec une actualité non publiée n\'a pas d\'actualités publiées', function () {
    $source = natFrontSource();
    $tool = natFrontTool('UNPUB');
    $article = natFrontArticle($source->id, 'UNPUB', false);

    $tool->newsArticles()->attach($article->id, ['source' => 'manual']);

    expect($tool->newsArticles()->published()->count())->toBe(0);
});

it('un outil avec des actualités publiées les renvoie dans la relation', function () {
    $source = natFrontSource();
    $tool = natFrontTool('PUB');
    $articleA = natFrontArticle($source->id, 'PUBA');
    $articleB = natFrontArticle($source->id, 'PUBB');

    $tool->newsArticles()->attach([$articleA->id => ['source' => 'manual'], $articleB->id => ['source' => 'manual']]);

    $linked = $tool->newsArticles()->published()->latest('pub_date')->limit(12)->get();
    expect($linked)->toHaveCount(2);
    expect($linked->pluck('id'))->toContain($articleA->id)->toContain($articleB->id);
});

// ── Tests HTTP sur la page actualité (News show, compatible SQLite) ───────────

it('page actualité : le bloc Outils mentionnés apparaît quand des outils sont liés', function () {
    $source = natFrontSource();
    $article = natFrontArticle($source->id, 'OBL');
    $tool = natFrontTool('OBL');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    $response->assertSee(__('Outils mentionnés'));
    $response->assertSee('Outil front OBL');
});

it('page actualité : le bloc Outils mentionnés est absent quand aucun outil n\'est lié', function () {
    $source = natFrontSource();
    $article = natFrontArticle($source->id, 'NOBL');

    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    $response->assertDontSee(__('Outils mentionnés'));
});
