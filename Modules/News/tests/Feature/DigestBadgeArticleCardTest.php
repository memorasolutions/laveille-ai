<?php

declare(strict_types=1);

/**
 * Actus 2.0 - badge « fiche comparative » sur la carte du fil /actualites.
 *
 * Avant ce correctif, is_comparative_digest n'était géré que par la page de détail
 * (show.blade.php) : une fiche comparative était indiscernable d'une actualité normale
 * dans le fil. Ces tests couvrent le partial article-card.blade.php, réutilisé par
 * /actualites ET par l'onglet Actualités des fiches outils (Directory).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (noms préfixés pour éviter toute collision globale avec
//    les helpers natFront* d'autres fichiers de tests News, tous deux chargés
//    dans le même process Pest). ────────────────────────────────────────────

function digestBadgeSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source badge test',
        'url' => 'https://digest-badge.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function digestBadgeArticle(int $sourceId, string $suffix, bool $isDigest, array $sources = []): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article badge {$suffix}",
        'guid' => "guid-digest-badge-{$suffix}",
        'url' => "https://exemple.com/digest-badge-{$suffix}",
        'description' => "Description de test pour badge {$suffix}",
        'slug' => 'article-digest-badge-' . strtolower($suffix),
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
        'is_comparative_digest' => $isDigest,
        'structured_summary' => $isDigest ? ['hook' => 'Fusion de test.', 'sources' => $sources] : null,
    ]);
}

// ── Tests positif / négatif ───────────────────────────────────────────────

it('liste des actualités : le badge fiche comparative apparaît avec le bon nombre de sources', function () {
    $source = digestBadgeSource();
    digestBadgeArticle(
        $source->id,
        'DIGEST',
        true,
        [
            ['source_name' => 'Source A', 'url' => 'https://a.exemple.com'],
            ['source_name' => 'Source B', 'url' => 'https://b.exemple.com'],
            ['source_name' => 'Source C', 'url' => 'https://c.exemple.com'],
        ]
    );

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertSee('nw-digest-badge', escape: false);
    $response->assertSee(__('Fiche comparative'));
    $response->assertSee('3 sources');
});

it('liste des actualités : une actualité normale n\'affiche pas le badge fiche comparative', function () {
    $source = digestBadgeSource();
    digestBadgeArticle($source->id, 'NORMAL', false);

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    // La classe CSS existe toujours une fois dans le <style> @once (définition), mais
    // le <span> rendu ne doit jamais apparaître pour une actualité non comparative.
    $response->assertDontSee(__('Fiche comparative'));
});

it('liste des actualités : le badge fiche comparative reste discret (1 seul source) sans faute d\'accord', function () {
    $source = digestBadgeSource();
    digestBadgeArticle(
        $source->id,
        'ONESOURCE',
        true,
        [['source_name' => 'Source unique', 'url' => 'https://unique.exemple.com']]
    );

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertSee('1 source');
    $response->assertDontSee('1 sources');
});

it('liste des actualités : le badge fiche comparative n\'ajoute aucune requête SQL par article (anti N+1)', function () {
    $source = digestBadgeSource();
    // 3 fiches comparatives sur la même page : le comptage de sources vient de la colonne
    // JSON structured_summary déjà chargée avec la ligne, jamais d'une requête séparée.
    digestBadgeArticle($source->id, 'N1', true, [['source_name' => 'A', 'url' => 'https://a.exemple.com']]);
    digestBadgeArticle($source->id, 'N2', true, [['source_name' => 'B', 'url' => 'https://b.exemple.com'], ['source_name' => 'C', 'url' => 'https://c.exemple.com']]);
    digestBadgeArticle($source->id, 'N3', true, []);

    DB::enableQueryLog();
    $response = $this->get(route('news.index'));
    // str_contains sur 'news_articles' seul (pas de guillemet d'identifiant) pour rester
    // indépendant du moteur (backticks MySQL en prod vs guillemets doubles SQLite en test).
    $articleQueries = collect(DB::getQueryLog())->pluck('query')->filter(
        fn (string $q) => str_starts_with($q, 'select *') && str_contains($q, 'news_articles')
    );
    DB::disableQueryLog();

    $response->assertStatus(200);
    // Une seule requête SELECT * sur news_articles pour toute la page, peu importe le
    // nombre de fiches comparatives affichées.
    expect($articleQueries)->toHaveCount(1);
});
