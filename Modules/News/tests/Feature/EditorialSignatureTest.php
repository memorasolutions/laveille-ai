<?php

declare(strict_types=1);

/**
 * Module « signature éditoriale » (signal humain E-E-A-T vérifiable, design doc
 * SPEC-SIGNAL-HUMAIN, décision club des sages 5 oracles notée 93/100, 2026-08-20). Couvre :
 * - NewsArticle::hasEditorialReview()/reviewerLabel() (modèle) ;
 * - le rendu conditionnel de x-news::editorial-signature ;
 * - JsonLdService::newsArticle() : reviewedBy présent/absent, author[0]/author[1] intacts
 *   (non-régression de NewsSeoEnrichedTest.php, fichier dédié pour ne pas l'alourdir) ;
 * - NewsApplyCommand : pose reviewed_at/reviewed_by sur une fiche réellement enrichie.
 *
 * Fichier dédié, helpers locaux préfixés `es` (Editorial Signature), autonomes - même convention
 * que ComposedSummaryApplyTest.php (préfixe `cs`) et NewsApplyCommandTest.php (préfixe `nac`).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\SEO\Services\JsonLdService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Es pour éviter tout conflit inter-fichiers) ────────────────

function esSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source signature éditoriale',
        'url' => 'https://es-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function esArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = esSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article signature éditoriale {$i}",
        'guid' => "guid-es-{$suffix}",
        'url' => "https://exemple.com/es-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-es-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
        'internal_source_text' => 'Texte source de test pour la signature éditoriale.',
        'source_content_hash' => hash('sha256', 'Texte source de test pour la signature éditoriale.'),
    ], $overrides));
}

function esPayloadFile(array $data): string
{
    $path = sys_get_temp_dir().'/es-payload-'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

function esFreshMeta(NewsArticle $article): array
{
    $article = $article->fresh();

    return [
        'expected_source_hash' => $article->source_content_hash,
        'expected_updated_at' => $article->updated_at?->toIso8601String(),
    ];
}

// ── NewsArticle::hasEditorialReview() / reviewerLabel() ─────────────────────────────────

it('hasEditorialReview() est faux tant que reviewed_at est absent', function () {
    $article = new NewsArticle(['title' => 'Sans relecture']);

    expect($article->hasEditorialReview())->toBeFalse();
});

it('hasEditorialReview() est vrai dès que reviewed_at est posé', function () {
    $article = new NewsArticle(['title' => 'Avec relecture', 'reviewed_at' => now()]);

    expect($article->hasEditorialReview())->toBeTrue();
});

it('reviewerLabel() retombe sur le libellé applicatif par défaut si reviewed_by est vide', function () {
    $article = new NewsArticle(['reviewed_at' => now(), 'reviewed_by' => null]);

    expect($article->reviewerLabel())->toBe('La rédaction de laveille.ai');
});

it('reviewerLabel() retourne reviewed_by quand il est posé', function () {
    $article = new NewsArticle(['reviewed_at' => now(), 'reviewed_by' => 'Un relecteur nommé']);

    expect($article->reviewerLabel())->toBe('Un relecteur nommé');
});

// ── Composant x-news::editorial-signature ───────────────────────────────────────────────

it('le composant editorial-signature ne rend rien sans relecture', function () {
    $article = new NewsArticle(['title' => 'Fiche jamais relue']);

    $html = Blade::render('<x-news::editorial-signature :article="$article" />', ['article' => $article]);

    expect(trim($html))->toBe('');
});

it('le composant editorial-signature rend la mention et le lien méthodologie si relue', function () {
    $article = new NewsArticle([
        'title' => 'Fiche relue',
        'reviewed_at' => \Carbon\Carbon::create(2026, 8, 20, 10, 0, 0),
        'reviewed_by' => null,
    ]);

    $html = Blade::render('<x-news::editorial-signature :article="$article" />', ['article' => $article]);

    expect($html)->toContain('Vérifié par')
        ->and($html)->toContain('La rédaction de laveille.ai')
        ->and($html)->toContain(route('methodologie'))
        ->and($html)->toContain('Notre méthodologie');
});

// ── JsonLdService::newsArticle() : reviewedBy ───────────────────────────────────────────

function esJsonLdArticle(array $overrides = []): NewsArticle
{
    $source = new NewsSource(['name' => 'TechCrunch', 'language' => 'en']);
    $article = new NewsArticle(array_merge([
        'title' => 'Article de test JSON-LD signature',
        'seo_title' => 'Article de test JSON-LD signature',
        'meta_description' => 'Description de test.',
        'description' => '',
        'structured_summary' => [
            'hook' => 'Accroche de test.',
            'key_points' => ['Point un.', 'Point deux.'],
            'why_important' => 'Parce que le test le dit.',
        ],
        'image_url' => 'https://laveille.ai/storage/news/images/es-test.webp',
        'category_tag' => 'IA générative',
        'pub_date' => now()->subHours(2),
        'updated_at' => now()->subHour(),
        'slug' => 'article-json-ld-signature',
        'url' => 'https://techcrunch.com/2026/08/20/test-signature',
        'resolved_url' => 'https://techcrunch.com/2026/08/20/test-signature',
    ], $overrides));
    $article->id = 999_998;
    $article->setRelation('source', $source);

    return $article;
}

it('reviewedBy est absent du JSON-LD tant que la fiche n\'a jamais été relue', function () {
    $article = esJsonLdArticle();

    $schema = JsonLdService::newsArticle($article);

    expect($schema)->not->toHaveKey('reviewedBy');
});

it('reviewedBy apparaît dans le JSON-LD une fois la fiche relue, sans jamais toucher author', function () {
    $reviewedAt = now()->subMinutes(10);
    $article = esJsonLdArticle(['reviewed_at' => $reviewedAt, 'reviewed_by' => null]);

    $schema = JsonLdService::newsArticle($article);

    expect($schema)->toHaveKey('reviewedBy')
        ->and($schema['reviewedBy']['@type'])->toBe('Organization')
        ->and($schema['reviewedBy']['name'])->toBe('La rédaction de laveille.ai')
        ->and($schema['dateModified'])->toBe($reviewedAt->toIso8601String());

    // Non-régression stricte : author[0]/author[1] intacts (contrat NewsSeoEnrichedTest.php).
    expect($schema['author'][0]['@type'])->toBe('Person')
        ->and($schema['author'][0]['name'])->toBe('Stéphane Lapointe')
        ->and($schema['author'][1]['@type'])->toBe('Organization')
        ->and($schema['author'][1]['name'])->toBe('TechCrunch');
});

// ── NewsApplyCommand : pose reviewed_at/reviewed_by côté serveur ────────────────────────

it('news:apply pose reviewed_at/reviewed_by quand composed_summary et editorial_proof_pairs sont tous deux présents', function () {
    $article = esArticle();
    $payload = esPayloadFile(array_merge(esFreshMeta($article), [
        'composed_summary' => ['hook' => 'Accroche composée de test.'],
        'editorial_proof_pairs' => [[
            'statement' => 'Le texte contient ce fait.',
            'excerpt' => 'Texte source de test pour la signature éditoriale.',
            'type' => 'fact',
        ]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article->refresh();
    expect($article->hasEditorialReview())->toBeTrue()
        ->and($article->reviewed_at)->not->toBeNull()
        ->and($article->reviewed_by)->toBe('La rédaction de laveille.ai');
});

it('news:apply ne pose pas reviewed_at si seul editorial_proof_pairs est fourni (pas de composed_summary)', function () {
    $article = esArticle();
    $payload = esPayloadFile(array_merge(esFreshMeta($article), [
        'editorial_proof_pairs' => [[
            'statement' => 'Le texte contient ce fait.',
            'excerpt' => 'Texte source de test pour la signature éditoriale.',
            'type' => 'fact',
        ]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->hasEditorialReview())->toBeFalse();
});

it('news:apply refuse toujours de laisser l\'agent poser lui-même reviewed_at (clé absente de la liste blanche)', function () {
    $article = esArticle();
    $payload = esPayloadFile(array_merge(esFreshMeta($article), [
        'seo_title' => 'Titre test',
        'reviewed_at' => '2020-01-01T00:00:00+00:00',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});
