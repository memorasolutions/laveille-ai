<?php

declare(strict_types=1);

/**
 * Tests admin : liaison actualités ↔ outils (Phase 2).
 *
 * Couvre : updateArticle sync tool_ids, suggestTools JSON, sécurité (non-admin bloqué).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ────────────────────────────────────────────────────────────

function natAdminSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source admin test',
        'url' => 'https://nat-admin.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function natAdminArticle(int $sourceId, string $suffix = 'A'): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article admin {$suffix}",
        'guid' => "guid-nat-admin-{$suffix}",
        'url' => "https://exemple.com/nat-admin-{$suffix}",
        'description' => "Description de test pour admin {$suffix}",
        'slug' => "article-admin-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

function natAdminTool(string $suffix = 'A'): Tool
{
    $name = "Outil admin {$suffix}";
    $slug = "outil-admin-" . strtolower($suffix);

    return Tool::withoutEvents(fn () => Tool::create([
        'name' => json_encode(['fr_CA' => $name, 'en' => $name]),
        'slug' => json_encode(['fr_CA' => $slug, 'en' => $slug]),
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

function natSuperAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $email = 'superadmin-nat@laveille.ai';
    $user = \App\Models\User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

// ── updateArticle : synchronisation des outils ──────────────────────────────

it('updateArticle synchronise les outils liés', function () {
    $admin = natSuperAdmin();
    $source = natAdminSource();
    $article = natAdminArticle($source->id, 'S');
    $toolA = natAdminTool('SA');
    $toolB = natAdminTool('SB');

    $response = $this->actingAs($admin)->put(route('admin.news.articles.update', $article), [
        '_method' => 'PUT',
        'tool_ids' => [$toolA->id, $toolB->id],
    ]);

    $response->assertRedirect();

    expect($article->fresh()->tools()->count())->toBe(2);
    expect($article->fresh()->tools()->pluck('directory_tools.id')->toArray())
        ->toContain($toolA->id)
        ->toContain($toolB->id);
});

it('updateArticle retire les outils quand tool_ids est vide', function () {
    $admin = natSuperAdmin();
    $source = natAdminSource();
    $article = natAdminArticle($source->id, 'R');
    $tool = natAdminTool('R');

    // Pré-lier l'outil
    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $response = $this->actingAs($admin)->put(route('admin.news.articles.update', $article), [
        '_method' => 'PUT',
        'tool_ids' => [],
    ]);

    $response->assertRedirect();

    expect($article->fresh()->tools()->count())->toBe(0);
});

it('updateArticle rejette un tool_id inexistant', function () {
    $admin = natSuperAdmin();
    $source = natAdminSource();
    $article = natAdminArticle($source->id, 'INV');

    $response = $this->actingAs($admin)->put(route('admin.news.articles.update', $article), [
        '_method' => 'PUT',
        'tool_ids' => [99999],
    ]);

    $response->assertSessionHasErrors('tool_ids.0');
});

// ── suggestTools ─────────────────────────────────────────────────────────────

it('suggestTools retourne un JSON avec la clé tool_ids', function () {
    $admin = natSuperAdmin();
    $source = natAdminSource();
    $article = natAdminArticle($source->id, 'SGG');

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.articles.suggest-tools', $article)
    );

    $response->assertStatus(200)->assertJsonStructure(['tool_ids']);
    expect($response->json('tool_ids'))->toBeArray();
});

it('suggestTools ne nécessite pas de token pour le corps (CSRF géré par Laravel)', function () {
    $admin = natSuperAdmin();
    $source = natAdminSource();
    $article = natAdminArticle($source->id, 'CSRF');

    // postJson ajoute automatiquement Accept: application/json (bypass CSRF en test)
    $response = $this->actingAs($admin)->postJson(
        route('admin.news.articles.suggest-tools', $article)
    );

    $response->assertStatus(200);
});

// ── Sécurité : non-admin bloqué ───────────────────────────────────────────────

it('updateArticle redirige un visiteur non authentifié', function () {
    $source = natAdminSource();
    $article = natAdminArticle($source->id, 'SEC');

    $response = $this->put(route('admin.news.articles.update', $article), [
        '_method' => 'PUT',
        'tool_ids' => [],
    ]);

    $response->assertRedirect();
});
