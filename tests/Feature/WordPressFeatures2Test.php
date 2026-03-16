<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

// --- Autosave ---

it('autosave updates article title', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $article = Article::factory()->create(['title' => 'Original']);

    $this->actingAs($user)
        ->patchJson(route('admin.blog.articles.autosave', $article), ['title' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($article->fresh()->title)->toBe('Updated');
});

it('autosave returns null saved_at when no changes', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $article = Article::factory()->create(['title' => 'Same']);

    $this->actingAs($user)
        ->patchJson(route('admin.blog.articles.autosave', $article), ['title' => 'Same'])
        ->assertOk()
        ->assertJsonPath('saved_at', null);
});

it('autosave requires authentication', function () {
    $article = Article::factory()->create();

    $this->patchJson(route('admin.blog.articles.autosave', $article), ['title' => 'Test'])
        ->assertUnauthorized();
});

// --- Media crop ---

it('media crop endpoint requires image_data', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->postJson(route('admin.media-api.crop', 999), [])
        ->assertUnprocessable();
});
