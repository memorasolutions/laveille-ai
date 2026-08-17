<?php

declare(strict_types=1);

/**
 * Tests admin : récupération automatique du texte source en Markdown (fetch-source) et bouton
 * Publier-et-purger (publish) - design doc "Actus - composition manuelle assistée" 2026-08-15,
 * section "Récupération automatique Markdown + Publier-et-purger (2026-08-17)".
 *
 * Fichier séparé de NewsCompositionBuilderTest.php (qui couvre déjà Phase A/B/D et le rendu de
 * l'écran) pour ne jamais entrer en conflit avec ce fichier existant - helpers locaux préfixés
 * `smf` (SourceMarkdownFetcher), autonomes, aucune dépendance croisée.
 *
 * Convention du projet : jamais d'appel réseau réel - Http::fake() et Process::fake() partout
 * (voir ActusZeroCopiePipelineTest.php et Modules/Directory/tests/Feature/
 * ScreenshotOverwriteGuardTest.php pour les mêmes conventions Pest).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Smf pour éviter tout conflit inter-fichiers) ──────

function smfSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source récupération Markdown',
        'url' => 'https://smf-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function smfArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Article Markdown {$i}",
        'guid' => "guid-smf-{$suffix}",
        'url' => "https://exemple-editeur.com/smf-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-smf-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

function smfAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// Corps d'article HTML plausible pour Readability : assez de texte (> 50 mots) dans un seul bloc
// <article>, sans bruit de navigation qui pourrait détourner l'extraction du contenu principal.
function smfArticleHtml(string $title = 'Un titre d\'article tout à fait ordinaire', int $paragraphs = 4): string
{
    $sentence = 'Ceci est une phrase de test qui décrit un événement technologique important survenu récemment au Québec et ailleurs dans le monde francophone. ';
    $body = str_repeat('<p>'.str_repeat($sentence, 3).'</p>', $paragraphs);

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

function smfPaywallHtml(): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><title>Article réservé</title></head>
<body>
<article>
<h1>Article réservé</h1>
<p>Abonnez-vous pour lire la suite de cet article exclusif réservé à nos abonnés premium qui soutiennent notre travail journalistique au quotidien depuis de nombreuses années déjà.</p>
</article>
</body>
</html>
HTML;
}

// ── fetch-source : garde-fou "texte existant" ──────────────────────────────────

it('fetch-source refuses to overwrite an existing source text without replace (409), nothing changes', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, ['internal_source_text' => 'MARQUEUR-TEXTE-DEJA-PRESENT']);

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.composition.fetch-source', $article),
        ['replace' => false]
    );

    $response->assertStatus(409);
    expect($article->fresh()->internal_source_text)->toBe('MARQUEUR-TEXTE-DEJA-PRESENT');
});

// ── fetch-source : succès HTTP direct ──────────────────────────────────────────

it('fetch-source persists the markdown, the acquisition trace and the raw markdown hash on HTTP success', function () {
    Http::fake([
        '*' => Http::response(smfArticleHtml('Titre récupéré chez l\'éditeur'), 200),
    ]);

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'internal_source_text' => null,
        'title' => 'Titre récupéré chez l\'éditeur',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertOk()->assertJson(['success' => true]);

    $article->refresh();
    expect($article->internal_source_text)->not->toBeNull()
        ->and($article->internal_source_text)->toContain('phrase de test')
        ->and($article->source_acquisition)->not->toBeNull()
        ->and($article->source_acquisition['method'])->toBe('http')
        ->and($article->source_acquisition['http_status'])->toBe(200)
        ->and($article->source_acquisition['raw_markdown_hash'])->toBe(hash('sha256', $article->internal_source_text))
        ->and($article->source_acquisition['word_count'])->toBeGreaterThan(50)
        // Provenance existante (section 5.2) réutilisée, même règle que update()/generatePrompt().
        ->and($article->source_content_hash)->toBe(hash('sha256', $article->internal_source_text))
        ->and($article->source_captured_at)->not->toBeNull();
});

// ── fetch-source : repli Puppeteer si l'étape HTTP échoue ──────────────────────

it('fetch-source falls back to the Puppeteer script when the HTTP step fails, and still persists on success', function () {
    Http::fake([
        '*' => Http::response('', 500),
    ]);
    Process::fake([
        '*' => Process::result(output: smfArticleHtml('Titre rendu par Puppeteer')),
    ]);

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'internal_source_text' => null,
        'title' => 'Titre rendu par Puppeteer',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertOk()->assertJson(['success' => true]);
    $article->refresh();
    expect($article->source_acquisition['method'])->toBe('puppeteer')
        ->and($article->internal_source_text)->toContain('phrase de test');
});

// ── fetch-source : 403/429 = échec immédiat, jamais d'acharnement Puppeteer ────

it('fetch-source stops immediately on HTTP 403 without falling back to Puppeteer', function () {
    Http::fake([
        '*' => Http::response('', 403),
    ]);
    Process::fake();

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, ['internal_source_text' => null]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(422);
    expect($response->json('error'))->toContain('403');
    Process::assertNothingRan();
    expect($article->fresh()->internal_source_text)->toBeNull();
});

// ── fetch-source : échec total (HTTP + Puppeteer) → rien n'est persisté ────────

it('fetch-source persists nothing when both the HTTP step and the Puppeteer fallback fail', function () {
    Http::fake([
        '*' => Http::response('', 404),
    ]);
    Process::fake([
        '*' => Process::result(output: '', exitCode: 1),
    ]);

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, ['internal_source_text' => null, 'source_acquisition' => null]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(422);
    $article->refresh();
    expect($article->internal_source_text)->toBeNull()
        ->and($article->source_acquisition)->toBeNull();
});

// ── fetch-source : validation tout-ou-rien (contenu trop court) ────────────────

it('fetch-source rejects content below the 50-word floor, persisting nothing', function () {
    Http::fake([
        '*' => Http::response('<html><head><title>Court</title></head><body><article><h1>Court</h1><p>Un texte bien trop court pour être un article complet.</p></article></body></html>', 200),
    ]);

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, ['internal_source_text' => null]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(422);
    expect($article->fresh()->internal_source_text)->toBeNull();
});

// ── fetch-source : mur d'abonnement détecté ─────────────────────────────────────

it('fetch-source rejects a subscription-wall page with an explicit message, persisting nothing', function () {
    Http::fake([
        '*' => Http::response(smfPaywallHtml(), 200),
    ]);

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, ['internal_source_text' => null]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(422);
    expect($response->json('error'))->toContain('abonnement');
    expect($article->fresh()->internal_source_text)->toBeNull();
});

// ── fetch-source : garde SSRF ────────────────────────────────────────────────────

it('fetch-source refuses a private/loopback IP target (SSRF guard), no HTTP call is made', function () {
    Http::fake();

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'internal_source_text' => null,
        'url' => 'http://127.0.0.1/secret-endpoint',
        'resolved_url' => null,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(422);
    Http::assertNothingSent();
    expect($article->fresh()->internal_source_text)->toBeNull();
});

it('fetch-source refuses a private-range IP target given via resolved_url', function () {
    Http::fake();

    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'internal_source_text' => null,
        'url' => 'https://exemple.com/article',
        'resolved_url' => 'http://10.0.0.5/internal',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(422);
    Http::assertNothingSent();
});

// ── fetch-source : accès refusé (403) sans texte existant, message explicite ──

it('a non-admin cannot call fetch-source (403)', function () {
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $source = smfSource();
    $article = smfArticle($source->id, ['internal_source_text' => null]);

    $response = $this->actingAs($user)->postJson(route('admin.news.composition.fetch-source', $article));

    $response->assertStatus(403);
});

// ── publish : prérequis serveur ──────────────────────────────────────────────

it('publish refuses with the full list of missing prerequisites (422), nothing is published', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'seo_title' => null,
        'summary' => null,
        'editorial_proof_pairs' => [],
        'is_published' => false,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.publish', $article));

    $response->assertStatus(422);
    $missing = $response->json('missing');
    expect($missing)->toContain('seo_title')
        ->and($missing)->toContain('summary')
        ->and($missing)->toContain('editorial_proof_pairs');
    expect($article->fresh()->is_published)->toBeFalse();
});

// ── publish : revalidation à 100 % des paires "fait" contre le texte COURANT ──

it('publish rejects when a "fact" pair is no longer an exact substring of the current source text - nothing published, nothing purged', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => 'Le texte source a changé depuis la création de la paire de preuve.',
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Une affirmation appuyée par une citation.',
            'excerpt' => 'un extrait qui ne figure plus dans le texte source actuel',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.publish', $article));

    $response->assertStatus(422);
    $article->refresh();
    expect($article->is_published)->toBeFalse()
        ->and($article->internal_source_text)->not->toBeNull()
        ->and($article->editorial_proof_pairs)->toHaveCount(1);
    $this->assertDatabaseHas('news_articles', ['id' => $article->id, 'is_published' => false]);
});

// ── publish : succès - publié + texte purgé + provenance/paires/acquisition survivent ──

it('publish succeeds: article published, internal_source_text purged, provenance/pairs/acquisition survive', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = smfArticle($source->id, [
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'source_captured_at' => now(),
        'source_acquisition' => ['method' => 'http', 'final_url' => 'https://exemple.com/article', 'http_status' => 200, 'word_count' => 15, 'fetched_at' => now()->toIso8601String(), 'raw_markdown_hash' => hash('sha256', $sourceText), 'warning' => null],
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Le ministère investit 12 millions.',
            'excerpt' => 'un investissement de 12 millions de dollars',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.publish', $article));

    $response->assertOk()->assertJson(['success' => true]);
    expect($response->json('site_url'))->toContain($article->slug);

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull()
        ->and($article->internal_source_text)->toBeNull()
        // Provenance, paires et trace d'acquisition SURVIVENT à la purge - même garde-fou que
        // destroySourceText() (design doc section 5.2).
        ->and($article->source_content_hash)->toBe(hash('sha256', $sourceText))
        ->and($article->source_captured_at)->not->toBeNull()
        ->and($article->source_acquisition)->not->toBeNull()
        ->and($article->editorial_proof_pairs)->toHaveCount(1);
});

// ── publish : bonification panel 2026-08-17 (soir) - primary_sources/image_credit SURVIVENT
// à la purge, même garde-fou que la provenance/les paires ci-dessus. ────────────────────────

it('publish preserves primary_sources and image_credit across the purge (HTTP button, same guard as news:apply)', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = smfArticle($source->id, [
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'primary_sources' => [
            ['label' => 'Communiqué officiel', 'url' => 'https://exemple-officiel.com/communique', 'note' => null],
        ],
        'image_credit' => 'Photo : Untel, Unsplash',
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Le ministère investit 12 millions.',
            'excerpt' => 'un investissement de 12 millions de dollars',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.publish', $article));

    $response->assertOk()->assertJson(['success' => true]);

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->internal_source_text)->toBeNull()
        ->and($article->primary_sources)->toHaveCount(1)
        ->and($article->image_credit)->toBe('Photo : Untel, Unsplash');
});

// ── publish : addendum 2026-08-17 - structured_summary (résumé machine) effacé juste avant
// la publication, même règle DRY que NewsApplyCommand --payload (voir NewsApplyCommandTest.php)
// (Modules\News\resources\views\public\show.blade.php affiche structured_summary EN PRIORITÉ) ──

it('publish also clears structured_summary (machine summary) so the composed summary becomes visible on the public page', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = smfArticle($source->id, [
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'structured_summary' => ['hook' => 'MARQUEUR-RESUME-MACHINE-PUBLISH'],
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Le ministère investit 12 millions.',
            'excerpt' => 'un investissement de 12 millions de dollars',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.publish', $article));

    $response->assertOk()->assertJson(['success' => true]);
    expect($article->fresh()->structured_summary)->toBeNull();
});

// ── publish : déjà publiée ───────────────────────────────────────────────────

it('publish refuses an already-published article (409)', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $article = smfArticle($source->id, [
        'seo_title' => 'Déjà en ligne',
        'summary' => 'Déjà en ligne.',
        'editorial_proof_pairs' => [['id' => 'p1', 'statement' => 's', 'excerpt' => 'e', 'type' => 'analysis', 'created_at' => now()->toIso8601String()]],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.publish', $article));

    $response->assertStatus(409);
});

it('a non-admin cannot call publish (403)', function () {
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $source = smfSource();
    $article = smfArticle($source->id, ['is_published' => false]);

    $response = $this->actingAs($user)->postJson(route('admin.news.composition.publish', $article));

    $response->assertStatus(403);
});

// ── Addendum 2026-08-17 : « purge garantie sur tous les chemins de publication » ───────────
// toggleArticle() (bascule rapide /admin/news/articles) et news:verify-source-purge (filet de
// vérification quotidien) - même garde-fou DRY que publish() ci-dessus
// (NewsArticle::publishAndPurgeSource()).

it('toggleArticle purges the internal source text when it publishes a draft, provenance/pairs/acquisition survive', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $sourceText = 'Texte source intégral qui doit disparaître dès la bascule rapide.';
    $article = smfArticle($source->id, [
        'is_published' => false,
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'source_captured_at' => now(),
        'source_acquisition' => ['method' => 'http', 'final_url' => 'https://exemple.com/a', 'http_status' => 200, 'word_count' => 10, 'fetched_at' => now()->toIso8601String(), 'raw_markdown_hash' => hash('sha256', $sourceText), 'warning' => null],
        'editorial_proof_pairs' => [['id' => 'p1', 'statement' => 's', 'excerpt' => 'e', 'type' => 'analysis', 'created_at' => now()->toIso8601String()]],
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.news.articles.toggle', $article));

    $response->assertRedirect();
    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull()
        ->and($article->internal_source_text)->toBeNull()
        ->and($article->source_content_hash)->toBe(hash('sha256', $sourceText))
        ->and($article->source_captured_at)->not->toBeNull()
        ->and($article->source_acquisition)->not->toBeNull()
        ->and($article->editorial_proof_pairs)->toHaveCount(1);
});

it('toggleArticle unpublishing an article does not touch the source text or published_at', function () {
    $admin = smfAdmin();
    $source = smfSource();
    $publishedAt = now()->subDays(3);
    $article = smfArticle($source->id, [
        'is_published' => true,
        'published_at' => $publishedAt,
        'internal_source_text' => null,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.news.articles.toggle', $article));

    $response->assertRedirect();
    $article->refresh();
    expect($article->is_published)->toBeFalse()
        ->and($article->internal_source_text)->toBeNull()
        ->and($article->published_at->toIso8601String())->toBe($publishedAt->toIso8601String());
});

// ── news:verify-source-purge ────────────────────────────────────────────────────

it('news:verify-source-purge purges a published article that still carries a residual source text', function () {
    $source = smfSource();
    $article = smfArticle($source->id, [
        'is_published' => true,
        'published_at' => now()->subHour(),
        'internal_source_text' => 'Texte résiduel oublié par un chemin de publication qui ne purge pas.',
        'source_content_hash' => hash('sha256', 'peu importe ici'),
    ]);

    $this->artisan('news:verify-source-purge')->assertExitCode(0);

    expect($article->fresh()->internal_source_text)->toBeNull()
        ->and($article->fresh()->source_content_hash)->not->toBeNull(); // provenance survit
});

it('news:verify-source-purge ignores drafts (never published) even with a source text present', function () {
    $source = smfSource();
    $article = smfArticle($source->id, [
        'is_published' => false,
        'internal_source_text' => 'Brouillon normal, texte source attendu ici.',
    ]);

    $this->artisan('news:verify-source-purge')->assertExitCode(0);

    expect($article->fresh()->internal_source_text)->toBe('Brouillon normal, texte source attendu ici.');
});

it('news:verify-source-purge is idempotent: a second run immediately after finds nothing left to purge', function () {
    $source = smfSource();
    smfArticle($source->id, [
        'is_published' => true,
        'published_at' => now()->subHour(),
        'internal_source_text' => 'Texte résiduel à purger une seule fois.',
    ]);

    $this->artisan('news:verify-source-purge')->assertExitCode(0);
    expect(NewsArticle::where('is_published', true)->whereNotNull('internal_source_text')->count())->toBe(0);

    // Deuxième passage immédiat : plus rien à purger.
    $this->artisan('news:verify-source-purge')
        ->expectsOutputToContain('Aucune fiche publiée')
        ->assertExitCode(0);
});
