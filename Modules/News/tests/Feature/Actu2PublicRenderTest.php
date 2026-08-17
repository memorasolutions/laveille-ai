<?php

declare(strict_types=1);

/**
 * Tests de rendu public de l'implémentation /actu2 - volet serveur (design doc "Actus -
 * composition manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur
 * (2026-08-17)") : Modules\News\resources\views\public\show.blade.php. Couvre le bloc de citation
 * statique d'un post X (`original_post`, jamais le widget platform.x.com) et le badge de
 * `niveau_preuve` traduit en français courant (jamais l'étiquette technique brute), dans les DEUX
 * branches d'affichage du corps (résumé structuré `$ss` et repli `$article->summary`).
 *
 * Fichier dédié, distinct de PrimarySourcesAndImageCreditPublicTest.php (autre bonification de la
 * même vue) - helpers locaux préfixés `a2r` (actu2 render), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ───────────────────────────────────────────────────────────────────

function a2rSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source rendu /actu2',
        'url' => 'https://a2r-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function a2rArticle(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article de test rendu /actu2 '.$slug,
        'guid' => 'guid-a2r-'.$slug,
        'url' => 'https://a2r-source.exemple.com/'.$slug,
        'resolved_url' => 'https://a2r-source.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

// ── Citation statique original_post : branche $ss (résumé structuré) ───────────────

it('renders the original_post citation with author, handle, date and link, never the platform.x.com widget', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'post-avec-resume-structure', [
        'structured_summary' => ['hook' => 'Accroche de test.', 'key_points' => ['Point clé.']],
        'original_post' => [
            'text' => 'MARQUEUR-CITATION-POST-ORIGINAL',
            'author' => 'Jeanne Tremblay',
            'handle' => '@jtremblay',
            'date' => '17 août 2026',
            'url' => 'https://x.com/jtremblay/status/1234567890',
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-post-quote', false)
        ->assertSee('MARQUEUR-CITATION-POST-ORIGINAL', false)
        ->assertSee('Jeanne Tremblay', false)
        ->assertSee('@jtremblay', false)
        ->assertSee('href="https://x.com/jtremblay/status/1234567890"', false)
        ->assertSee('rel="noopener nofollow"', false)
        ->assertDontSee('platform.x.com', false)
        ->assertDontSee('twitter-widget', false);
});

// ── Citation statique original_post : branche @elseif($article->summary) (repli) ───

it('renders the same original_post citation when falling back to $article->summary (no structured_summary)', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'post-sans-resume-structure', [
        'structured_summary' => null,
        'original_post' => ['text' => 'MARQUEUR-CITATION-POST-REPLI'],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-post-quote', false)
        ->assertSee('MARQUEUR-CITATION-POST-REPLI', false)
        ->assertDontSee('platform.x.com', false);
});

it('never renders the original_post block when original_post is absent', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'sans-post-original', ['original_post' => null]);

    $response = $this->get(route('news.show', $article));

    // La classe .nw-post-quote est TOUJOURS définie dans le <style> de la vue (même convention
    // que PrimarySourcesAndImageCreditPublicTest.php pour nw-image-credit) : c'est l'ÉLÉMENT
    // rendu qu'on doit viser, pas le nom de classe seul.
    $response->assertOk()->assertDontSee('<blockquote class="nw-post-quote">', false);
});

// ── Badge niveau_preuve : traduction française, jamais l'étiquette technique brute ─

it('renders the translated niveau_preuve badge (primaire), never the raw technical label', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'preuve-primaire', ['niveau_preuve' => 'primaire']);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-niveau-preuve', false)
        ->assertSee('Fondée sur la source originale', false)
        ->assertDontSee('>primaire<', false);
});

it('renders the translated niveau_preuve badge (mixte)', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'preuve-mixte', ['niveau_preuve' => 'mixte']);

    $response = $this->get(route('news.show', $article))->assertOk();

    $response->assertSee('Sources originale et média', false);
});

it('renders the translated niveau_preuve badge (relais)', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'preuve-relais', ['niveau_preuve' => 'relais']);

    $response = $this->get(route('news.show', $article))->assertOk();

    $response->assertSee('D&#039;après un média relais', false);
});

it('never renders the niveau_preuve badge when the field is absent', function () {
    $source = a2rSource();
    $article = a2rArticle($source->id, 'sans-niveau-preuve', ['niveau_preuve' => null]);

    $response = $this->get(route('news.show', $article));

    // Même convention que ci-dessus : la classe est TOUJOURS définie dans le <style> de la vue,
    // on vise l'ÉLÉMENT rendu (le <p> du badge), pas le nom de classe seul.
    $response->assertOk()->assertDontSee('<p class="nw-niveau-preuve">', false);
});
