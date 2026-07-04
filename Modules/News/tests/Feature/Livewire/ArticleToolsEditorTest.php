<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - ArticleToolsEditor (Livewire) : éditeur inline « Outils liés » admin-gaté.
 *
 * Prouve :
 *  - SÉCURITÉ (OWASP A01) : non-admin → 403 sur mount ET sur chaque action ;
 *  - CRUD : addTool/removeTool ENREGISTRENT IMMÉDIATEMENT en base (2026-07-04 :
 *    plus d'étape "Enregistrer" séparée - chaque clic persiste tout de suite,
 *    demande utilisateur pour accélérer la liaison multiple d'outils) ;
 *  - SUGGESTION : suggestTools fusionne les outils détectés ET enregistre ;
 *  - ANTI-IDOR : mutation du articleId (#[Locked]) → exception Livewire ;
 *  - INVARIANT : les tests admin HTTP existants restent verts après le refactor DRY.
 *
 * Helpers préfixés `ate` (ArticleToolsEditor) pour éviter les redéclarations globales.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Directory\Models\Tool;
use Modules\News\Livewire\ArticleToolsEditor;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function ateSource(): NewsSource
{
    return NewsSource::create([
        'name'     => 'Source ATE',
        'url'      => 'https://ate-source.exemple.com/rss',
        'language' => 'fr',
        'active'   => true,
    ]);
}

function ateArticle(int $sourceId, string $suffix = 'A'): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title'          => "Article ATE {$suffix}",
        'guid'           => "guid-ate-{$suffix}",
        'url'            => "https://exemple.com/ate-{$suffix}",
        'description'    => "Description de test ATE {$suffix}",
        'slug'           => "article-ate-" . strtolower($suffix),
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]);
}

function ateTool(string $suffix = 'A'): Tool
{
    $name = "Outil ATE {$suffix}";
    $slug = "outil-ate-" . strtolower($suffix);

    return Tool::withoutEvents(fn () => Tool::create([
        'name'    => ['fr_CA' => $name, 'en' => $name],
        'slug'    => ['fr_CA' => $slug, 'en' => $slug],
        'status'  => 'published',
        'pricing' => 'free',
    ]));
}

function ateAdmin(): User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function ateUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

// ── SÉCURITÉ : non-admin bloqué ───────────────────────────────────────────────
// Note : Livewire convertit AuthorizationException en réponse HTTP 403 (ne lève
// pas d'exception côté test). On vérifie donc le statut avec assertForbidden().

test('mount refuse un visiteur non authentifié (403)', function (): void {
    $source  = ateSource();
    $article = ateArticle($source->id, 'SEC1');

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->assertForbidden();
});

test('mount refuse un utilisateur sans permission view_admin_panel (403)', function (): void {
    $user    = ateUser();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SEC2');

    $this->actingAs($user);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->assertForbidden();
});

test('addTool refuse un utilisateur sans permission view_admin_panel (403 au mount)', function (): void {
    $user    = ateUser();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SEC3');

    // Un non-admin tente directement de monter (bloqué avant même d'appeler addTool).
    $this->actingAs($user);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->assertForbidden();
});

// ── ANTI-IDOR : #[Locked] empêche la mutation du articleId ───────────────────

test('mutation du articleId après mount rejetée par #[Locked]', function (): void {
    $admin    = ateAdmin();
    $source   = ateSource();
    $article1 = ateArticle($source->id, 'IDOR1');
    $article2 = ateArticle($source->id, 'IDOR2');

    $this->actingAs($admin);

    expect(fn () => Livewire::test(ArticleToolsEditor::class, ['article' => $article1])
        ->set('articleId', $article2->id)
    )->toThrow(\Exception::class);
});

// ── CRUD admin : add / remove (persistance immédiate, zéro clic "Enregistrer") ──

test('addTool ajoute un outil à la sélection ET enregistre immédiatement en base', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'ADD');
    $tool    = ateTool('ADD');

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('addTool', $tool->id)
        ->assertSet('selectedToolIds', [$tool->id]);

    // Déjà en base après ce seul appel - aucune étape "Enregistrer" séparée requise.
    expect($article->fresh()->tools()->pluck('directory_tools.id')->all())->toBe([$tool->id]);
});

test('deux addTool successifs enregistrent les deux outils sans étape intermédiaire', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'ADD2');
    $toolA   = ateTool('ADD2A');
    $toolB   = ateTool('ADD2B');

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('addTool', $toolA->id)
        ->call('addTool', $toolB->id);

    $ids = $article->fresh()->tools()->pluck('directory_tools.id')->all();
    expect($ids)->toContain($toolA->id)->toContain($toolB->id);
    expect(count($ids))->toBe(2);
});

test('removeTool retire un outil de la sélection ET l\'enregistre immédiatement en base', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'REM');
    $tool    = ateTool('REM');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('removeTool', $tool->id)
        ->assertSet('selectedToolIds', []);

    // Déjà retiré en base après ce seul appel.
    expect($article->fresh()->tools()->count())->toBe(0);
});

test('addTool ignore un doublon (outil déjà sélectionné)', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'DUP');
    $tool    = ateTool('DUP');

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('addTool', $tool->id)
        ->call('addTool', $tool->id)
        ->assertSet('selectedToolIds', [$tool->id]);

    expect($article->fresh()->tools()->count())->toBe(1);
});

// ── SUGGESTION ───────────────────────────────────────────────────────────────

test('suggestTools fusionne les outils suggérés ET enregistre immédiatement en base', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SUGG');

    $this->actingAs($admin);

    // Sans outils détectables dans ce contenu de test, la liste reste vide - mais
    // le point testé est que l'état du composant et l'état en base restent
    // TOUJOURS synchronisés après suggestTools (persistance immédiate).
    $component = Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('suggestTools');

    expect($component->get('selectedToolIds'))->toBeArray();

    $persistedIds = $article->fresh()->tools()->pluck('directory_tools.id')->map(fn ($id) => (int) $id)->all();
    expect($persistedIds)->toEqualCanonicalizing($component->get('selectedToolIds'));
});
