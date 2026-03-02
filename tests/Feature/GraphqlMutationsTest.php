<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

uses(RefreshDatabase::class, MakesGraphQLRequests::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ============================================================
// Profile mutations
// ============================================================

test('updateProfile requires authentication', function () {
    $response = $this->graphQL('
        mutation {
            updateProfile(input: { name: "John", bio: "Test" }) { id name }
        }
    ');

    $response->assertGraphQLErrorMessage('Unauthenticated.');
});

test('updateProfile updates name and bio', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            updateProfile(input: { name: "Nouveau Nom", bio: "Ma bio" }) {
                id name bio
            }
        }
    ');

    $response->assertJsonPath('data.updateProfile.name', 'Nouveau Nom');
    $response->assertJsonPath('data.updateProfile.bio', 'Ma bio');

    expect($user->fresh()->name)->toBe('Nouveau Nom');
    expect($user->fresh()->bio)->toBe('Ma bio');
});

// ============================================================
// Article mutations
// ============================================================

test('createArticle requires auth and manage_articles permission', function () {
    // Without auth
    $response = $this->graphQL('
        mutation {
            createArticle(input: { title: "Test", content: "<p>C</p>", status: "draft" }) { id }
        }
    ');
    $response->assertGraphQLErrorMessage('Unauthenticated.');

    // With auth but without permission
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            createArticle(input: { title: "Test", content: "<p>C</p>", status: "draft" }) { id }
        }
    ');
    $response->assertGraphQLErrorMessage('This action is unauthorized.');
});

test('createArticle creates article successfully', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_articles');

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            createArticle(input: {
                title: "Mon article de test",
                content: "<p>Contenu de test</p>",
                excerpt: "Résumé",
                status: "draft"
            }) {
                id title slug content status
            }
        }
    ');

    $response->assertJsonPath('data.createArticle.title', 'Mon article de test');
    $response->assertJsonPath('data.createArticle.slug', 'mon-article-de-test');
    $response->assertJsonPath('data.createArticle.status', 'draft');

    expect(Article::withoutGlobalScopes()->count())->toBe(1);
});

test('updateArticle updates article title', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_articles');

    $article = Article::factory()->published()->for($user, 'user')->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation($input: UpdateArticleInput!) {
            updateArticle(input: $input) { id title }
        }
    ', ['input' => [
        'id' => (string) $article->id,
        'title' => 'Titre modifié',
    ]]);

    $response->assertJsonPath('data.updateArticle.title', 'Titre modifié');
});

test('deleteArticle soft-deletes article', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_articles');

    $article = Article::factory()->for($user, 'user')->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation($id: ID!) {
            deleteArticle(id: $id) { id }
        }
    ', ['id' => (string) $article->id]);

    $response->assertJsonPath('data.deleteArticle.id', (string) $article->id);
    expect($article->fresh()->trashed())->toBeTrue();
});

// ============================================================
// Page mutations
// ============================================================

test('createPage requires manage_pages permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            createPage(input: {
                title: "Test", content: "<p>C</p>", template: "default", status: "draft"
            }) { id }
        }
    ');

    $response->assertGraphQLErrorMessage('This action is unauthorized.');
});

test('createPage creates page successfully', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_pages');

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            createPage(input: {
                title: "Ma page de test",
                content: "<p>Contenu page</p>",
                template: "default",
                status: "published"
            }) {
                id title slug template status
            }
        }
    ');

    $response->assertJsonPath('data.createPage.title', 'Ma page de test');
    $response->assertJsonPath('data.createPage.slug', 'ma-page-de-test');
    $response->assertJsonPath('data.createPage.template', 'default');

    expect(StaticPage::withoutGlobalScopes()->count())->toBe(1);
});

test('updatePage updates page title', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_pages');

    $page = StaticPage::factory()->published()->for($user, 'user')->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation($input: UpdatePageInput!) {
            updatePage(input: $input) { id title }
        }
    ', ['input' => [
        'id' => (string) $page->id,
        'title' => 'Page modifiée',
    ]]);

    $response->assertJsonPath('data.updatePage.title', 'Page modifiée');
});

test('deletePage soft-deletes page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_pages');

    $page = StaticPage::factory()->for($user, 'user')->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation($id: ID!) {
            deletePage(id: $id) { id }
        }
    ', ['id' => (string) $page->id]);

    $response->assertJsonPath('data.deletePage.id', (string) $page->id);
    expect($page->fresh()->trashed())->toBeTrue();
});

// ============================================================
// Validation tests
// ============================================================

test('createArticle fails validation with empty title', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_articles');

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            createArticle(input: { title: "", content: "<p>C</p>", status: "draft" }) { id }
        }
    ');

    $response->assertGraphQLValidationError('input.title', __('validation.required', ['attribute' => 'input.title']));
});

test('user without permission gets unauthorized on createArticle', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->graphQL('
        mutation {
            createArticle(input: { title: "Test", content: "<p>C</p>", status: "draft" }) { id }
        }
    ');

    $response->assertGraphQLErrorMessage('This action is unauthorized.');
});
