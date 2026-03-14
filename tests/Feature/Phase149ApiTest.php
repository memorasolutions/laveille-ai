<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Blog\Models\Article;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->user = User::factory()->create();
});

// --- Public Blog API ---

it('lists published articles paginated', function () {
    Article::factory()->count(3)->published()->create();
    Article::factory()->draft()->create();

    $response = $this->getJson('/api/v1/articles');
    $response->assertOk()->assertJsonPath('success', true);

    $data = $response->json('data');
    $items = is_array($data) && isset($data['data']) ? $data['data'] : $data;
    expect(count($items))->toBe(3);
});

it('shows a single published article by slug', function () {
    $article = Article::factory()->published()->create();
    $slug = $article->getTranslation('slug', 'fr');

    $this->getJson("/api/v1/articles/{$slug}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data' => ['article', 'comments']]);
});

it('returns 404 for nonexistent article slug', function () {
    $this->getJson('/api/v1/articles/nonexistent-slug-xyz')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('lists blog categories', function () {
    Article::factory()->published()->create(['category' => 'Laravel']);
    Article::factory()->published()->create(['category' => 'PHP']);

    $this->getJson('/api/v1/blog/categories')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('searches articles by query', function () {
    $article = Article::factory()->published()->create();

    $title = $article->getTranslation('title', 'fr');
    $searchTerm = mb_substr($title, 0, 5);

    $this->getJson('/api/v1/blog/search?q='.urlencode($searchTerm))
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('validates search query is required', function () {
    $this->getJson('/api/v1/blog/search')
        ->assertUnprocessable();
});

it('validates search query min length', function () {
    $this->getJson('/api/v1/blog/search?q=x')
        ->assertUnprocessable();
});

it('excludes draft articles from public listing', function () {
    Article::factory()->draft()->create();

    $response = $this->getJson('/api/v1/articles');
    $response->assertOk();

    $data = $response->json('data');
    $items = is_array($data) && isset($data['data']) ? $data['data'] : $data;
    expect(count($items))->toBe(0);
});

// --- Public Plans API ---

it('lists active plans', function () {
    Plan::factory()->count(2)->create(['is_active' => true]);
    Plan::factory()->inactive()->create();

    $response = $this->getJson('/api/v1/plans');
    $response->assertOk();

    $data = $response->json('data');
    expect(count($data))->toBe(2);
});

it('shows a single plan by slug', function () {
    $plan = Plan::factory()->create(['is_active' => true]);

    $this->getJson("/api/v1/plans/{$plan->slug}")
        ->assertOk();
});

it('returns 404 for nonexistent plan slug', function () {
    $this->getJson('/api/v1/plans/nonexistent-plan-slug')
        ->assertNotFound();
});

// --- Authenticated Articles CRUD ---

it('creates an article as authenticated user', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/articles', [
        'title' => 'Mon nouvel article',
        'content' => 'Contenu de test pour article API',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('articles', ['user_id' => $this->user->id]);
});

it('validates title required when creating article', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/articles', [
        'content' => 'Contenu sans titre',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('validates content required when creating article', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/articles', [
        'title' => 'Titre sans contenu',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

it('updates own article', function () {
    Sanctum::actingAs($this->user);
    $article = Article::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

    $this->putJson("/api/v1/articles/{$article->id}", [
        'title' => 'Titre mis à jour',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('forbids updating another user article', function () {
    Sanctum::actingAs($this->user);
    $article = Article::factory()->create(['user_id' => $this->admin->id, 'status' => 'draft']);

    $this->putJson("/api/v1/articles/{$article->id}", [
        'title' => 'Tentative modification',
    ])
        ->assertForbidden();
});

it('deletes own article', function () {
    Sanctum::actingAs($this->user);
    $article = Article::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

    $this->deleteJson("/api/v1/articles/{$article->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('articles', ['id' => $article->id]);
});

it('forbids deleting another user article', function () {
    Sanctum::actingAs($this->user);
    $article = Article::factory()->create(['user_id' => $this->admin->id, 'status' => 'draft']);

    $this->deleteJson("/api/v1/articles/{$article->id}")
        ->assertForbidden();
});

// --- Comments API ---

it('creates a comment on an article', function () {
    Sanctum::actingAs($this->user);
    $article = Article::factory()->published()->create();

    $this->postJson("/api/v1/articles/{$article->id}/comments", [
        'content' => 'Excellent article !',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

it('validates comment content is required', function () {
    Sanctum::actingAs($this->user);
    $article = Article::factory()->published()->create();

    $this->postJson("/api/v1/articles/{$article->id}/comments", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

// --- Auth required ---

it('returns 401 for unauthenticated article creation', function () {
    $this->postJson('/api/v1/articles', [
        'title' => 'Test',
        'content' => 'Test',
    ])
        ->assertUnauthorized();
});

it('returns 401 for unauthenticated article deletion', function () {
    $article = Article::factory()->create(['status' => 'draft']);

    $this->deleteJson("/api/v1/articles/{$article->id}")
        ->assertUnauthorized();
});

it('admin can update any article', function () {
    Sanctum::actingAs($this->admin);
    $article = Article::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

    $this->putJson("/api/v1/articles/{$article->id}", [
        'title' => 'Modifié par admin',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);
});
