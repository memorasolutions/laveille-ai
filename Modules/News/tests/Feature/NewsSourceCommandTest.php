<?php

declare(strict_types=1);

/**
 * Tests de la commande news:source - récolte serveur de l'ORIGINAL pour le skill Claude Code
 * local /actu2 (design doc "Actus - composition manuelle assistée" 2026-08-15, section
 * "Implémentation /actu2 - volet serveur (2026-08-17)"). Couvre : le refus sur une fiche publiée,
 * le refus d'écraser un texte source déjà présent sans --replace, la persistance du Markdown +
 * provenance + trace d'acquisition sur succès, et le JSON de sortie (hash + updated_at).
 *
 * Convention du projet : jamais d'appel réseau réel - Http::fake()/Process::fake() (mêmes
 * fixtures HTML que SourceMarkdownFetchPublishTest.php, dupliquées ici en helpers locaux préfixés
 * `nsc` pour rester autonome, cohérent avec la convention déjà appliquée par ce fichier soeur).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nsc pour éviter tout conflit inter-fichiers) ──────────────

function nscSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source news:source',
        'url' => 'https://nsc-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function nscArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = nscSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article news:source {$i}",
        'guid' => "guid-nsc-{$suffix}",
        'url' => "https://exemple-editeur.com/nsc-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-nsc-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

// Corps d'article HTML plausible pour Readability (> 50 mots, un seul bloc <article>) - même
// gabarit que SourceMarkdownFetchPublishTest.php::smfArticleHtml().
function nscArticleHtml(string $title = 'Titre de l\'original retrouvé par le skill'): string
{
    $sentence = 'Ceci est une phrase de test qui décrit un événement technologique important survenu récemment au Québec et ailleurs dans le monde francophone. ';
    $body = str_repeat('<p>'.str_repeat($sentence, 3).'</p>', 4);

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><title>{$title}</title><meta charset="utf-8"></head>
<body>
<nav>Accueil | À propos | Contact</nav>
<article>
<h1>{$title}</h1>
{$body}
</article>
<footer>Tous droits réservés.</footer>
</body>
</html>
HTML;
}

// ── Refus sur fiche publiée ──────────────────────────────────────────────────────────

it('news:source refuses to write on an already-published article', function () {
    Http::fake();
    $article = nscArticle(['is_published' => true]);

    $this->artisan('news:source', ['article' => $article->id, 'url' => 'https://exemple-editeur.com/original'])
        ->assertFailed();

    Http::assertNothingSent();
});

it('news:source refuses when the article does not exist', function () {
    Http::fake();

    $this->artisan('news:source', ['article' => 999999, 'url' => 'https://exemple-editeur.com/original'])
        ->assertFailed();
});

// ── Refus d'écraser sans --replace ──────────────────────────────────────────────────

it('news:source refuses to overwrite an existing source text without --replace, nothing changes', function () {
    Http::fake();
    $article = nscArticle(['internal_source_text' => 'MARQUEUR-TEXTE-DEJA-PRESENT']);

    $this->artisan('news:source', ['article' => $article->id, 'url' => 'https://exemple-editeur.com/original'])
        ->assertFailed();

    Http::assertNothingSent();
    expect($article->fresh()->internal_source_text)->toBe('MARQUEUR-TEXTE-DEJA-PRESENT');
});

it('news:source overwrites an existing source text when --replace is given', function () {
    Http::fake([
        '*' => Http::response(nscArticleHtml('Nouveau titre remplacé'), 200),
    ]);
    $article = nscArticle(['internal_source_text' => 'ANCIEN-TEXTE-A-REMPLACER', 'title' => 'Nouveau titre remplacé']);

    $this->artisan('news:source', ['article' => $article->id, 'url' => 'https://exemple-editeur.com/original', '--replace' => true])
        ->assertSuccessful();

    expect($article->fresh()->internal_source_text)
        ->not->toBe('ANCIEN-TEXTE-A-REMPLACER')
        ->toContain('phrase de test');
});

// ── Succès : Markdown + provenance + trace d'acquisition persistés ─────────────────

it('news:source persists the markdown, source_acquisition and provenance on success, and outputs hash + updated_at as JSON', function () {
    Http::fake([
        '*' => Http::response(nscArticleHtml('Titre de l\'original retrouvé par le skill'), 200),
    ]);
    $article = nscArticle([
        'internal_source_text' => null,
        'title' => 'Titre de l\'original retrouvé par le skill',
    ]);

    Artisan::call('news:source', ['article' => $article->id, 'url' => 'https://exemple-editeur.com/original']);
    $output = trim(Artisan::output());
    $decoded = json_decode($output, true);

    $article->refresh();
    expect($article->internal_source_text)->not->toBeNull()
        ->and($article->internal_source_text)->toContain('phrase de test')
        ->and($article->source_acquisition)->not->toBeNull()
        ->and($article->source_acquisition['method'])->toBe('http')
        ->and($article->source_content_hash)->toBe(hash('sha256', $article->internal_source_text))
        ->and($article->source_captured_at)->not->toBeNull();

    expect($decoded)->not->toBeNull()
        ->and($decoded['success'])->toBeTrue()
        ->and($decoded['article_id'])->toBe($article->id)
        ->and($decoded['source_content_hash'])->toBe($article->source_content_hash)
        ->and($decoded['updated_at'])->toBe($article->updated_at->toIso8601String());
});

// ── Échec de la récupération : rien n'est persisté ──────────────────────────────────

it('news:source persists nothing when the fetch fails (404, no Puppeteer fallback configured)', function () {
    Http::fake([
        '*' => Http::response('', 404),
    ]);
    \Illuminate\Support\Facades\Process::fake([
        '*' => \Illuminate\Support\Facades\Process::result(output: '', exitCode: 1),
    ]);
    $article = nscArticle(['internal_source_text' => null, 'source_acquisition' => null]);

    $this->artisan('news:source', ['article' => $article->id, 'url' => 'https://exemple-editeur.com/introuvable'])
        ->assertFailed();

    $article->refresh();
    expect($article->internal_source_text)->toBeNull()
        ->and($article->source_acquisition)->toBeNull();
});

// ── Garde SSRF (réutilise SourceMarkdownFetcher::guardUrl(), même comportement) ────

it('news:source refuses a private/loopback IP target (SSRF guard), no HTTP call is made', function () {
    Http::fake();
    $article = nscArticle(['internal_source_text' => null]);

    $this->artisan('news:source', ['article' => $article->id, 'url' => 'http://127.0.0.1/secret'])
        ->assertFailed();

    Http::assertNothingSent();
    expect($article->fresh()->internal_source_text)->toBeNull();
});

// ── Journalisation (canal dédié 'composition') ──────────────────────────────────────

it('news:source writes to the dedicated composition log file', function () {
    $logPath = storage_path('logs/composition-'.now()->format('Y-m-d').'.log');
    @unlink($logPath);

    Http::fake([
        '*' => Http::response(nscArticleHtml(), 200),
    ]);
    $article = nscArticle(['internal_source_text' => null]);

    $this->artisan('news:source', ['article' => $article->id, 'url' => 'https://exemple-editeur.com/original'])
        ->assertSuccessful();

    expect(file_exists($logPath))->toBeTrue();
    $content = file_get_contents($logPath);
    expect($content)->toContain((string) $article->id);

    @unlink($logPath);
});
