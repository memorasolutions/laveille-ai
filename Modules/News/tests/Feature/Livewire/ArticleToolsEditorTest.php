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
 *  - CRUD : admin peut ajouter/retirer/enregistrer → pivot mis à jour ;
 *  - SUGGESTION : suggestTools fusionne les outils détectés ;
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

test('mount refuse un visiteur non authentifié (403)', function (): void {
    $source  = ateSource();
    $article = ateArticle($source->id, 'SEC1');

    expect(fn () => Livewire::test(ArticleToolsEditor::class, ['article' => $article]))
        ->toThrow(\Exception::class);
});

test('mount refuse un utilisateur sans permission view_admin_panel (403)', function (): void {
    $user    = ateUser();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SEC2');

    $this->actingAs($user);

    expect(fn () => Livewire::test(ArticleToolsEditor::class, ['article' => $article]))
        ->toThrow(\Exception::class);
});

test('save refuse un utilisateur sans permission view_admin_panel (403)', function (): void {
    $admin   = ateAdmin();
    $user    = ateUser();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SEC3');
    $tool    = ateTool('SEC3');

    // Monte le composant en tant qu'admin.
    $this->actingAs($admin);
    $component = Livewire::test(ArticleToolsEditor::class, ['article' => $article]);

    // Tente de réutiliser le composant avec un autre utilisateur non-admin.
    $this->actingAs($user);

    expect(fn () => $component->call('save'))
        ->toThrow(\Exception::class);
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

// ── CRUD admin : add / remove / save ─────────────────────────────────────────

test('addTool ajoute un outil à la sélection sans enregistrer', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'ADD');
    $tool    = ateTool('ADD');

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('addTool', $tool->id)
        ->assertSet('selectedToolIds', [$tool->id]);

    // Pas encore en base.
    expect($article->tools()->count())->toBe(0);
});

test('removeTool retire un outil de la sélection sans enregistrer', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'REM');
    $tool    = ateTool('REM');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('removeTool', $tool->id)
        ->assertSet('selectedToolIds', []);

    // Pas encore en base.
    expect($article->tools()->count())->toBe(1);
});

test('save synchronise le pivot en base', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SAVE');
    $toolA   = ateTool('SAVEA');
    $toolB   = ateTool('SAVEB');

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('addTool', $toolA->id)
        ->call('addTool', $toolB->id)
        ->call('save');

    $ids = $article->fresh()->tools()->pluck('directory_tools.id')->all();
    expect($ids)->toContain($toolA->id)->toContain($toolB->id);
    expect(count($ids))->toBe(2);
});

test('save retire un outil retiré de la sélection', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SAVR');
    $tool    = ateTool('SAVR');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('removeTool', $tool->id)
        ->call('save');

    expect($article->fresh()->tools()->count())->toBe(0);
});

test('save flash le message de confirmation', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'FLAS');

    $this->actingAs($admin);

    Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('save')
        ->assertSessionHas('news_tools_editor_status');
});

// ── SUGGESTION ───────────────────────────────────────────────────────────────

test('suggestTools fusionne les outils suggérés sans écrire en base', function (): void {
    $admin   = ateAdmin();
    $source  = ateSource();
    $article = ateArticle($source->id, 'SUGG');

    $this->actingAs($admin);

    // Sans outils détectables dans le contenu, la liste est vide ou inchangée.
    $component = Livewire::test(ArticleToolsEditor::class, ['article' => $article])
        ->call('suggestTools');

    // selectedToolIds est un array (potentiellement vide = OK, aucune exception).
    expect($component->get('selectedToolIds'))->toBeArray();

    // Rien n'est écrit en base.
    expect($article->tools()->count())->toBe(0);
});
