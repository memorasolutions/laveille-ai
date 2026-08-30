<?php

declare(strict_types=1);

/**
 * Tests de la liste blanche news:apply étendue par l'implémentation /actu2 - volet serveur
 * (design doc "Actus - composition manuelle assistée" 2026-08-15, section "Implémentation /actu2
 * - volet serveur (2026-08-17)") : nature_original (enum), niveau_preuve (enum) et original_post
 * ({text, author, handle, date, url}) rejoignent la liste blanche de Modules\News\Console\
 * NewsApplyCommand, avec les mêmes garde-fous de validation stricte que les clés existantes.
 *
 * Fichier dédié, distinct de NewsApplyCommandTest.php (qui couvre déjà seo_title/summary/
 * editorial_proof_pairs/primary_sources/image_credit) - helpers locaux préfixés `a2p` (actu2
 * payload), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés A2p pour éviter tout conflit inter-fichiers) ──────────────

function a2pSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source /actu2 payload',
        'url' => 'https://a2p-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function a2pArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = a2pSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article /actu2 payload {$i}",
        'guid' => "guid-a2p-{$suffix}",
        'url' => "https://exemple.com/a2p-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-a2p-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

function a2pPayloadFile(array $data): string
{
    $path = sys_get_temp_dir().'/a2p-payload-'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

function a2pFreshMeta(NewsArticle $article): array
{
    $article = $article->fresh();

    return [
        'expected_source_hash' => $article->source_content_hash,
        'expected_updated_at' => $article->updated_at?->toIso8601String(),
    ];
}

// ── nature_original : valeurs valides / invalides ───────────────────────────────────

it('applies a valid nature_original value', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['nature_original' => 'etude_evaluee']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->nature_original)->toBe('etude_evaluee');
});

it('refuses an invalid nature_original value, persisting nothing', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['nature_original' => 'valeur-inventee']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->nature_original)->toBeNull();
});

// ── Ticket #1915 (2026-08-30) : trois valeurs ajoutées après mesure de 129 fiches publiées
// (niveau_preuve non nul) où AUCUNE des 4 valeurs d'origine ne convenait - contenu_educatif,
// projet_communautaire, entrevue_publiee. Rouge avant ce ticket (refusées, exactement comme
// 'valeur-inventee' ci-dessus) ; vert après. Source unique : NewsArticle::NATURE_ORIGINAL_VALUES.

it('applies the newly added nature_original value contenu_educatif (ticket #1915)', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['nature_original' => 'contenu_educatif']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->nature_original)->toBe('contenu_educatif');
});

it('applies the newly added nature_original value projet_communautaire (ticket #1915)', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['nature_original' => 'projet_communautaire']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->nature_original)->toBe('projet_communautaire');
});

it('applies the newly added nature_original value entrevue_publiee (ticket #1915)', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['nature_original' => 'entrevue_publiee']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->nature_original)->toBe('entrevue_publiee');
});

it('still refuses a value absent from NewsArticle::NATURE_ORIGINAL_VALUES after the widening', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['nature_original' => 'encore-inventee']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->nature_original)->toBeNull();
});

it('NewsArticle::NATURE_ORIGINAL_VALUES carries the seven expected keys, each with a non-empty French label', function () {
    expect(array_keys(NewsArticle::NATURE_ORIGINAL_VALUES))->toBe([
        'annonce_commerciale',
        'etude_evaluee',
        'preimpression',
        'message_personnel',
        'contenu_educatif',
        'projet_communautaire',
        'entrevue_publiee',
    ]);

    foreach (NewsArticle::NATURE_ORIGINAL_VALUES as $valeur => $libelle) {
        expect($libelle)->toBeString()->not->toBe('');
    }
});

// ── niveau_preuve : valeurs valides / invalides ─────────────────────────────────────

it('applies a valid niveau_preuve value', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['niveau_preuve' => 'mixte']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->niveau_preuve)->toBe('mixte');
});

it('refuses an invalid niveau_preuve value, persisting nothing', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), ['niveau_preuve' => 'valeur-inventee']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->niveau_preuve)->toBeNull();
});

// ── original_post : valide / invalide ───────────────────────────────────────────────

it('applies a valid original_post (text, author, handle, date, url), persisted as-is', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), [
        'original_post' => [
            'text' => 'Voici l\'annonce originale, telle que publiée.',
            'author' => 'Jeanne Tremblay',
            'handle' => '@jtremblay',
            'date' => '17 août 2026',
            'url' => 'https://x.com/jtremblay/status/1234567890',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $post = $article->fresh()->original_post;
    expect($post['text'])->toBe('Voici l\'annonce originale, telle que publiée.')
        ->and($post['author'])->toBe('Jeanne Tremblay')
        ->and($post['handle'])->toBe('@jtremblay')
        ->and($post['date'])->toBe('17 août 2026')
        ->and($post['url'])->toBe('https://x.com/jtremblay/status/1234567890');
});

it('applies a minimal original_post with only text (all other fields optional)', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), [
        'original_post' => ['text' => 'Citation minimale, sans auteur ni lien.'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->original_post)->toBe(['text' => 'Citation minimale, sans auteur ni lien.']);
});

it('refuses an original_post without text', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), [
        'original_post' => ['author' => 'Jeanne Tremblay'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->original_post)->toBeNull();
});

it('refuses an original_post whose url is not a valid http/https URL', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), [
        'original_post' => ['text' => 'Citation.', 'url' => 'ceci-n-est-pas-une-url'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->original_post)->toBeNull();
});

it('refuses an original_post containing an unknown key', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), [
        'original_post' => ['text' => 'Citation.', 'clef_inventee' => 'valeur'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->original_post)->toBeNull();
});

it('refuses an original_post whose text exceeds 1000 characters', function () {
    $article = a2pArticle();
    $payload = a2pPayloadFile(array_merge(a2pFreshMeta($article), [
        'original_post' => ['text' => str_repeat('a', 1001)],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->original_post)->toBeNull();
});

// ── Survie à la publication-purge (même garde-fou que primary_sources/image_credit) ─

it('nature_original, niveau_preuve and original_post survive the publish-and-purge transaction', function () {
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = a2pArticle([
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'nature_original' => 'annonce_commerciale',
        'niveau_preuve' => 'primaire',
        'original_post' => ['text' => 'Citation qui doit survivre à la purge.'],
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Le ministère investit 12 millions.',
            'excerpt' => 'un investissement de 12 millions de dollars',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertSuccessful();

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->internal_source_text)->toBeNull()
        ->and($article->nature_original)->toBe('annonce_commerciale')
        ->and($article->niveau_preuve)->toBe('primaire')
        ->and($article->original_post)->toBe(['text' => 'Citation qui doit survivre à la purge.']);
});
