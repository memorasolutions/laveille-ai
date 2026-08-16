<?php

declare(strict_types=1);

/**
 * Tests admin : écran de composition manuelle d'une actualité (Phase A - design doc "Actus -
 * composition manuelle assistée", 2026-08-15). Couvre l'accès (invité / non-admin / admin), la
 * persistance du texte source interne, sa suppression dédiée, et surtout le garde-fou le plus
 * important du chantier : ce texte ne doit JAMAIS apparaître dans une vue publique (page fiche,
 * JSON-LD, index).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Ncb pour éviter tout conflit inter-fichiers) ──────

function ncbSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source composition',
        'url' => 'https://ncb-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function ncbArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Article composition {$i}",
        'guid' => "guid-ncb-{$suffix}",
        'url' => "https://exemple.com/ncb-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-ncb-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

function ncbAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function ncbRegularUser(): \App\Models\User
{
    return \App\Models\User::factory()->create(['email_verified_at' => now()]);
}

// ── Accès (mêmes droits que le Concentré : EnsureIsAdmin / view_admin_panel) ──

it('redirects guest to login', function () {
    $this->get(route('admin.news.composition.index'))->assertRedirect(route('login'));
});

it('blocks a regular user without view_admin_panel from the composition screen (403)', function () {
    $user = ncbRegularUser();

    $response = $this->actingAs($user)->get(route('admin.news.composition.index'));

    $response->assertStatus(403);
});

it('allows an admin to view the composition screen', function () {
    $admin = ncbAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk();
    $response->assertSee('compositionBuilder(', false);
    $response->assertSee('news-article-picker.js', false);
});

// ── Persistance du texte source ────────────────────────────────────────────────

it('an admin can save the internal source text of a selected article', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'seo_title' => 'Titre composé de test',
        'summary' => 'Résumé composé de test.',
        'internal_source_text' => 'MARQUEUR-TEXTE-SOURCE-INTERNE-DE-TEST',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    $article->refresh();
    expect($article->internal_source_text)->toBe('MARQUEUR-TEXTE-SOURCE-INTERNE-DE-TEST')
        ->and($article->seo_title)->toBe('Titre composé de test')
        ->and($article->summary)->toBe('Résumé composé de test.')
        ->and($article->description)->toBe(''); // jamais réutilisée, purgée
});

it('a non-admin cannot save the internal source text (403), nothing is written', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $response = $this->actingAs($user)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => 'ne doit jamais être écrit',
    ]);

    $response->assertStatus(403);
    expect($article->fresh()->internal_source_text)->toBeNull();
});

it('omitting a field on update does not null out the others (sometimes rule)', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['seo_title' => 'Titre original conservé']);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => 'Texte source seul.',
    ])->assertOk();

    $article->refresh();
    expect($article->seo_title)->toBe('Titre original conservé')
        ->and($article->internal_source_text)->toBe('Texte source seul.');
});

it('an admin can delete the internal source text without touching the rest of the fiche', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-A-SUPPRIMER',
        'seo_title' => 'Titre qui doit survivre',
    ]);

    $response = $this->actingAs($admin)->deleteJson(route('admin.news.composition.destroy-source-text', $article));

    $response->assertOk()->assertJson(['success' => true]);
    $article->refresh();
    expect($article->internal_source_text)->toBeNull()
        ->and($article->seo_title)->toBe('Titre qui doit survivre');
});

it('a non-admin cannot delete the internal source text (403)', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'MARQUEUR-DOIT-SURVIVRE']);

    $response = $this->actingAs($user)->deleteJson(route('admin.news.composition.destroy-source-text', $article));

    $response->assertStatus(403);
    expect($article->fresh()->internal_source_text)->toBe('MARQUEUR-DOIT-SURVIVRE');
});

it('the show() endpoint returns the internal source text only to an admin', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'MARQUEUR-VISIBLE-ADMIN-SEULEMENT']);

    $response = $this->actingAs($admin)->getJson(route('admin.news.composition.show', $article));

    $response->assertOk()->assertJsonPath('internal_source_text', 'MARQUEUR-VISIBLE-ADMIN-SEULEMENT');
});

it('the candidates() endpoint truncates the summary and never includes the internal source text', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    ncbArticle($source->id, ['internal_source_text' => 'MARQUEUR-JAMAIS-DANS-LA-LISTE']);

    $response = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $response->assertOk();
    $payload = json_encode($response->json());
    expect($payload)->not->toContain('MARQUEUR-JAMAIS-DANS-LA-LISTE')
        ->and($payload)->not->toContain('internal_source_text');
});

// ── Garde-fou le plus important : jamais dans une vue publique ────────────────

it('the internal source text never appears on the public article page', function () {
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-TEXTE-SOURCE-JAMAIS-PUBLIC-XYZ',
        'summary' => 'Résumé public normal, sans rapport avec le texte source.',
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk();
    $response->assertDontSee('MARQUEUR-TEXTE-SOURCE-JAMAIS-PUBLIC-XYZ', false);
});

it('the internal source text is absent from the JSON-LD of the public article page', function () {
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-JSONLD-JAMAIS-XYZ',
        'structured_summary' => [
            'hook' => 'Accroche visible du test JSON-LD.',
            'key_points' => ['Un point clé.'],
            'why_important' => 'Une explication.',
        ],
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk();
    $html = $response->getContent();
    expect($html)->toContain('Accroche visible du test JSON-LD.')
        ->and($html)->not->toContain('MARQUEUR-JSONLD-JAMAIS-XYZ');
});

it('the internal source text never appears on the public news index (article-card)', function () {
    $source = ncbSource();
    ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-INDEX-JAMAIS-XYZ',
    ]);

    $response = $this->get(route('news.index'));

    $response->assertOk();
    $response->assertDontSee('MARQUEUR-INDEX-JAMAIS-XYZ', false);
});

// ── Journalisation : même garde-fou que 'description' (voir ActusZeroCopiePipelineTest) ──

it('NewsArticle::activitylogFields does not include internal_source_text', function () {
    $article = new NewsArticle();
    $fields = (fn () => $this->activitylogFields)->call($article);

    expect($fields)->not->toContain('internal_source_text')
        ->and($fields)->toContain('title'); // le tableau reste non vide, pas une régression de portée
});

it('updating internal_source_text alone creates no activity log entry (not dirty-loggable)', function () {
    $source = ncbSource();
    $article = ncbArticle($source->id);
    $countBefore = \Spatie\Activitylog\Models\Activity::count();

    $article->update(['internal_source_text' => 'MARQUEUR-JAMAIS-JOURNALISE']);

    expect(\Spatie\Activitylog\Models\Activity::count())->toBe($countBefore);
});
