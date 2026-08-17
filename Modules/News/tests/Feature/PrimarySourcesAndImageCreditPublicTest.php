<?php

declare(strict_types=1);

/**
 * Bonification panel 2026-08-17 (soir) - décision du propriétaire (design doc "Actus -
 * composition manuelle assistée" 2026-08-15, section "Bonification panel 2026-08-17 (soir)") :
 * les fiches doivent CITER l'original (sources primaires visibles) et porter une PHOTO créditée.
 * Couvre le rendu public de Modules\News\resources\views\public\show.blade.php : la section
 * « Sources » en fin de fiche (primaires d'abord, puis le relais média renommé) et le crédit
 * photo discret sous l'image principale - dans les DEUX branches d'affichage du corps de la fiche
 * (résumé structuré $ss en priorité, résumé court $article->summary en repli), puisque la
 * section Sources vit APRÈS ce branchement dans la vue.
 *
 * Fichier dédié, distinct de NewsCompositionBuilderTest.php (écran de composition, hors
 * périmètre ici) et de QuoteAttribution292Test.php (autre bloc de la même vue) - helpers locaux
 * préfixés `psc` (Primary Sources & Credit), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ───────────────────────────────────────────────────────────────────

function pscSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source crédit photo',
        'url' => 'https://psc-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function pscArticle(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article de test sources primaires '.$slug,
        'guid' => 'guid-psc-'.$slug,
        'url' => 'https://psc-source.exemple.com/'.$slug,
        'resolved_url' => 'https://psc-source.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
        'primary_sources' => [
            ['label' => 'Communiqué officiel', 'url' => 'https://exemple-officiel.com/communique', 'note' => 'Chiffre confirmé'],
        ],
        'image_credit' => 'Photo : Untel, Unsplash',
        'image_url' => '/images/psc-'.$slug.'.jpg',
    ], $overrides));
}

// ── Section « Sources » : branche $ss (résumé structuré) ───────────────────────────

it('displays the Sources section with the primary source link and the renamed media relay when structured_summary is present', function () {
    $source = pscSource();
    $article = pscArticle($source->id, 'avec-resume-structure', [
        'structured_summary' => [
            'hook' => 'Accroche de test.',
            'key_points' => ['Point clé de test.'],
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Sources', false)
        ->assertSee('href="https://exemple-officiel.com/communique"', false)
        ->assertSee('Communiqué officiel', false)
        ->assertSee('rel="noopener nofollow"', false)
        // Le relais média existant reste présent, mais renommé.
        ->assertSee('Relais média :', false)
        ->assertSee('href="https://psc-source.exemple.com/avec-resume-structure-resolu"', false);
});

// ── Section « Sources » : branche @elseif($article->summary) (repli) ───────────────

it('displays the Sources section the same way when the article falls back to $article->summary (no structured_summary)', function () {
    $source = pscSource();
    $article = pscArticle($source->id, 'sans-resume-structure', [
        'structured_summary' => null,
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Sources', false)
        ->assertSee('href="https://exemple-officiel.com/communique"', false)
        ->assertSee('Communiqué officiel', false)
        ->assertSee('Relais média :', false);
});

// ── Crédit photo ─────────────────────────────────────────────────────────────────

it('displays the image credit discreetly under the hero image when image_credit is present', function () {
    $source = pscSource();
    $article = pscArticle($source->id, 'avec-credit-photo', [
        'image_credit' => 'Photo : Jeanne Tremblay, Unsplash',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-image-credit', false)
        ->assertSee('Photo : Jeanne Tremblay, Unsplash', false);
});

it('never displays an image credit block when image_credit is absent', function () {
    $source = pscSource();
    $article = pscArticle($source->id, 'sans-credit-photo', [
        'image_credit' => null,
    ]);

    $response = $this->get(route('news.show', $article));

    // La classe .nw-image-credit est TOUJOURS définie dans le <style> de la vue : c'est
    // l'ÉLÉMENT rendu qu'on doit viser, pas le nom de classe.
    $response->assertOk()->assertDontSee('<p class="nw-image-credit"', false);
});

// ── Absence de primary_sources : comportement antérieur inchangé (aucune régression) ──

it('falls back to the single-source CTA block when primary_sources is empty, exactly as before this bonification', function () {
    $source = pscSource();
    $article = pscArticle($source->id, 'sans-sources-primaires', [
        'primary_sources' => null,
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Voir l&#039;article original', false)
        ->assertDontSee('Relais média :', false);
});
