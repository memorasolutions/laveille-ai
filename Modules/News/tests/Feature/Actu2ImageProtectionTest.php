<?php

declare(strict_types=1);

/**
 * Protection des fiches curatées contre le pipeline machine (incident 2026-08-18 : la photo
 * générée de la fiche 33558 a été écrasée par la vignette de marque 20 minutes après sa
 * publication, par news:reprocess --unresolved-only planifié à :15 toutes les 2 heures).
 * Deux gardes testées : exclusion à la SÉLECTION (fiche manuelle / fiche composée) et
 * hasCuratedImage() (défense en profondeur, image avec crédit jamais régénérée).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function aipManualSource(): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => 'manuel://soumission-directe'],
        ['name' => 'Soumission manuelle', 'language' => 'fr', 'active' => false]
    );
}

function aipArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Fiche protection image {$i}",
        'guid' => "guid-aip-{$suffix}",
        'url' => "https://exemple.com/aip-{$suffix}",
        'description' => '',
        'summary' => 'Résumé.',
        'slug' => "aip-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

it('hasCuratedImage est vrai dès qu un crédit d image est présent', function () {
    $source = aipManualSource();
    $avec = aipArticle($source->id, ['image_credit' => 'Image : générée (Gemini)']);
    $sans = aipArticle($source->id);

    expect($avec->hasCuratedImage())->toBeTrue()
        ->and($sans->hasCuratedImage())->toBeFalse();
});

it('news:reprocess exclut les fiches manuelles et composées de sa sélection', function () {
    $manuelle = aipArticle(aipManualSource()->id, ['image_credit' => 'Image : générée (Gemini)']);

    $rss = NewsSource::create([
        'name' => 'Source RSS protection', 'url' => 'https://aip-rss.exemple.com/rss',
        'language' => 'fr', 'active' => true,
    ]);
    $composee = aipArticle($rss->id, ['structured_summary' => ['composed' => true, 'hook' => 'Accroche.']]);

    // dry-run : aucune écriture, mais la liste des articles traités est affichée.
    $this->artisan('news:reprocess', ['--unresolved-only' => true, '--dry-run' => true, '--limit' => 50])
        ->doesntExpectOutputToContain("[{$manuelle->id}]")
        ->doesntExpectOutputToContain("[{$composee->id}]")
        ->assertSuccessful();
});
