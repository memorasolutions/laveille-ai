<?php

declare(strict_types=1);

/**
 * Tests de la commande news:brief - point d'entrée LECTURE SEULE du skill Claude Code local
 * /actu2 (design doc "Actus - composition manuelle assistée" 2026-08-15, section
 * "Implémentation /actu2 - volet serveur (2026-08-17)"). Couvre : la fiche introuvable, la forme
 * du JSON canonique sorti sur stdout (tous les champs contractuels, y compris policy_version =
 * CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION), et l'absence TOTALE d'écriture (lecture
 * seule stricte).
 *
 * Fichier dédié, distinct de NewsApplyCommandTest.php et SourceMarkdownFetchPublishTest.php -
 * helpers locaux préfixés `nbc` (News Brief Command), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\CompositionPromptBuilder;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nbc pour éviter tout conflit inter-fichiers) ──────────────

function nbcSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source news:brief',
        'url' => 'https://nbc-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function nbcArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = nbcSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article news:brief {$i}",
        'guid' => "guid-nbc-{$suffix}",
        'url' => "https://exemple.com/nbc-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-nbc-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

// ── Fiche introuvable ────────────────────────────────────────────────────────────────

it('news:brief refuses when the article does not exist', function () {
    $this->artisan('news:brief', ['article' => 999999])->assertFailed();
});

// ── Forme du JSON canonique ──────────────────────────────────────────────────────────

it('news:brief outputs the full canonical JSON on stdout, including the current policy_version', function () {
    $sourceText = 'Texte source déjà collecté pour cette fiche.';
    $article = nbcArticle([
        'resolved_url' => 'https://exemple.com/resolue',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'source_captured_at' => now(),
        'primary_sources' => [
            ['label' => 'Communiqué officiel', 'url' => 'https://exemple-officiel.com/communique', 'note' => null],
        ],
        'nature_original' => 'etude_evaluee',
        'niveau_preuve' => 'primaire',
        'is_published' => false,
    ]);

    $exitCode = \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $output = trim(\Illuminate\Support\Facades\Artisan::output());
    $decoded = json_decode($output, true);

    expect($exitCode)->toBe(0)
        ->and($decoded)->not->toBeNull()
        ->and($decoded['id'])->toBe($article->id)
        ->and($decoded['slug'])->toBe($article->slug)
        ->and($decoded['title'])->toBe($article->title)
        ->and($decoded['url'])->toBe($article->url)
        ->and($decoded['resolved_url'])->toBe('https://exemple.com/resolue')
        ->and($decoded['is_published'])->toBeFalse()
        ->and($decoded['source_content_hash'])->toBe(hash('sha256', $sourceText))
        ->and($decoded['source_captured_at'])->not->toBeNull()
        ->and($decoded['updated_at'])->not->toBeNull()
        ->and($decoded['primary_sources'])->toHaveCount(1)
        ->and($decoded['nature_original'])->toBe('etude_evaluee')
        ->and($decoded['niveau_preuve'])->toBe('primaire')
        ->and($decoded['has_image'])->toBeFalse()
        ->and($decoded['policy_version'])->toBe(CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION)
        ->and($decoded['site_url'])->toContain($article->slug);
});

// ── Défaut 1 (2026-08-28) : structured_summary était absent du JSON canonique, forçant à le
// reconstruire depuis le HTML rendu pour corriger une seule phrase d'une fiche publiée ─────────

it('news:brief renders the composed structured_summary of an article that already has one', function () {
    $article = nbcArticle([
        'structured_summary' => [
            'composed' => true,
            'hook' => 'Accroche composée de test.',
            'key_points' => ['Premier point clé.', 'Deuxième point clé.'],
            'why_important' => 'Ça compte pour telle raison précise.',
        ],
    ]);

    $exitCode = \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $decoded = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);

    expect($exitCode)->toBe(0)
        ->and($decoded['structured_summary'])->not->toBeNull()
        ->and($decoded['structured_summary']['composed'])->toBeTrue()
        ->and($decoded['structured_summary']['hook'])->toBe('Accroche composée de test.')
        ->and($decoded['structured_summary']['key_points'])->toBe(['Premier point clé.', 'Deuxième point clé.'])
        ->and($decoded['structured_summary']['why_important'])->toBe('Ça compte pour telle raison précise.');
});

it('news:brief renders structured_summary as null (present in the JSON, jamais omis) when the article has none', function () {
    $article = nbcArticle();

    \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $decoded = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);

    expect(array_key_exists('structured_summary', $decoded))->toBeTrue()
        ->and($decoded['structured_summary'])->toBeNull();
});

it('news:brief reports has_image=true when a processed image already exists for the article', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $article = nbcArticle();
    \Illuminate\Support\Facades\Storage::disk('public')->put("news/images/{$article->id}.webp", 'contenu-image-factice');

    $output = null;
    \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $output = trim(\Illuminate\Support\Facades\Artisan::output());

    expect(json_decode($output, true)['has_image'])->toBeTrue();
});

// ── Lecture seule stricte : aucune écriture, quel que soit l'état de la fiche ───────

it('news:brief never writes anything, even when called repeatedly', function () {
    $article = nbcArticle(['seo_title' => null]);
    $before = $article->fresh()->updated_at?->toIso8601String();

    $this->artisan('news:brief', ['article' => $article->id])->assertSuccessful();
    $this->artisan('news:brief', ['article' => $article->id])->assertSuccessful();

    $after = $article->fresh();
    expect($after->updated_at?->toIso8601String())->toBe($before)
        ->and($after->seo_title)->toBeNull();
});

// ── Ticket #2237 : le « prévol » de /actu2 (étape 0, cette même commande) ne vérifiait que
// is_published, jamais ce que publishReadinessCheck() exige réellement à la publication - d'où 7
// fiches sur 9 refusées la nuit du 2026-09-03 malgré un prévol qui les avait laissées passer. DRY
// strict : publish_readiness DÉLÈGUE à NewsArticle::publishReadinessCheck(), jamais une
// réimplémentation partielle des mêmes règles. ────────────────────────────────────────────

it('news:brief exposes publish_readiness with the full list of missing fields when the fiche is not ready', function () {
    $article = nbcArticle([
        'seo_title' => null,
        'summary' => null,
        'editorial_proof_pairs' => null,
        'image_credit' => null,
    ]);

    \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $decoded = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);

    expect($decoded['publish_readiness']['ready'])->toBeFalse()
        ->and($decoded['publish_readiness']['missing'])->toBe(['seo_title', 'summary', 'editorial_proof_pairs', 'image_credit'])
        ->and($decoded['publish_readiness']['invalid_pair'])->toBeNull();
});

it('news:brief exposes publish_readiness as ready when every requirement of publishReadinessCheck is met', function () {
    $article = nbcArticle([
        'seo_title' => 'Un titre pour Google',
        'summary' => 'Un chapo complet.',
        'editorial_proof_pairs' => [[
            'statement' => 'Une affirmation éditoriale.',
            'excerpt' => 'un extrait confirmé à la source primaire',
            'type' => 'primary_fact',
            'source_url' => 'https://exemple.com/source-primaire',
        ]],
        'image_credit' => 'Image : générée (Gemini)',
    ]);

    \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $decoded = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);

    expect($decoded['publish_readiness']['ready'])->toBeTrue()
        ->and($decoded['publish_readiness']['missing'])->toBe([])
        ->and($decoded['publish_readiness']['invalid_pair'])->toBeNull();
});

it('news:brief exposes the invalid_pair verdict of publishReadinessCheck, pas seulement les champs manquants (preuve de délégation réelle)', function () {
    $article = nbcArticle([
        'seo_title' => 'Un titre pour Google',
        'summary' => 'Un chapo complet.',
        'internal_source_text' => 'Le texte source réellement collecté.',
        'editorial_proof_pairs' => [[
            'statement' => 'Une affirmation qui ne correspond à rien.',
            'excerpt' => "un extrait qui n'existe pas dans le texte source",
            'type' => 'fact',
        ]],
        'image_credit' => 'Image : générée (Gemini)',
    ]);

    \Illuminate\Support\Facades\Artisan::call('news:brief', ['article' => $article->id]);
    $decoded = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);

    expect($decoded['publish_readiness']['ready'])->toBeFalse()
        ->and($decoded['publish_readiness']['missing'])->toBe([])
        ->and($decoded['publish_readiness']['invalid_pair']['reason'])->toBe('fact_substring');
});
