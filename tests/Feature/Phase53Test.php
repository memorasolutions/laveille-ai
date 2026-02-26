<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Blog\Models\Article;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

// Profile API
test('api profile returns authenticated user', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonFragment(['name' => 'John Doe']);
});

test('api profile requires auth', function () {
    $this->getJson('/api/v1/profile')
        ->assertUnauthorized();
});

test('api profile update name', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonFragment(['name' => 'New Name']);
});

test('api profile update validates name required', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/v1/profile', ['name' => ''])
        ->assertUnprocessable();
});

test('api profile update bio', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', ['name' => $user->name, 'bio' => 'Ma bio'])
        ->assertOk();

    expect($user->fresh()->bio)->toBe('Ma bio');
});

// Plans API
test('api plans index returns active plans only', function () {
    Plan::factory()->create(['name' => 'Active Plan', 'slug' => 'active', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Hidden Plan', 'slug' => 'hidden', 'is_active' => false]);

    $this->getJson('/api/v1/plans')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Active Plan'])
        ->assertJsonMissing(['name' => 'Hidden Plan']);
});

test('api plans show by slug', function () {
    Plan::factory()->create(['slug' => 'pro', 'name' => 'Pro', 'is_active' => true]);

    $this->getJson('/api/v1/plans/pro')
        ->assertOk()
        ->assertJsonFragment(['slug' => 'pro']);
});

test('api plans show returns 404 for unknown slug', function () {
    $this->getJson('/api/v1/plans/nonexistent')
        ->assertNotFound();
});

// Articles API
test('api articles store requires auth', function () {
    $this->postJson('/api/v1/articles')
        ->assertUnauthorized();
});

test('api articles store creates article', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/articles', [
        'title' => 'Mon article API',
        'content' => 'Contenu de test',
    ])->assertCreated()
        ->assertJsonFragment(['title' => 'Mon article API']);
});

test('api articles store validates title required', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/articles', ['content' => 'Sans titre'])
        ->assertUnprocessable();
});

test('api articles store defaults to draft status', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/articles', [
        'title' => 'Draft article',
        'content' => 'Contenu',
    ])->assertCreated();

    expect(Article::first()->status->getValue())->toBe('draft');
});

test('api articles update own article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    $this->putJson("/api/v1/articles/{$article->id}", ['title' => 'Titre modifié'])
        ->assertOk()
        ->assertJsonFragment(['title' => 'Titre modifié']);
});

test('api articles delete own article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/articles/{$article->id}")
        ->assertOk();

    expect(Article::withTrashed()->find($article->id)->trashed())->toBeTrue();
});

test('api articles delete requires auth', function () {
    $article = Article::factory()->create();

    $this->deleteJson("/api/v1/articles/{$article->id}")
        ->assertUnauthorized();
});
