<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));

    $this->editor = User::factory()->create();
    $this->editor->assignRole(Role::findByName('editor', 'web'));

    $this->user = User::factory()->create();
});

// --- Authentification et autorisations ---

it('redirige un visiteur pour la liste des articles', function () {
    $this->get('/admin/blog/articles')->assertRedirect();
});

it('interdit le blog admin à un utilisateur classique', function () {
    $this->actingAs($this->user)
        ->get('/admin/blog/articles')
        ->assertForbidden();
});

// --- Pages chargent correctement ---

it('affiche la liste des articles pour un admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/blog/articles')
        ->assertOk();
});

it('affiche la page de création d un article', function () {
    $this->actingAs($this->admin)
        ->get('/admin/blog/articles/create')
        ->assertOk();
});

it('affiche la liste des catégories', function () {
    $this->actingAs($this->admin)
        ->get('/admin/blog/categories')
        ->assertOk();
});

it('affiche la liste des commentaires', function () {
    $this->actingAs($this->admin)
        ->get('/admin/blog/comments')
        ->assertOk();
});

// --- Article CRUD ---

it('permet de créer un article', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->post('/admin/blog/articles', [
            'title' => 'Mon nouvel article test',
            'content' => 'Contenu de l\'article.',
            'category_id' => $category->id,
        ])
        ->assertRedirect();

    expect(Article::where('title->fr', 'Mon nouvel article test')->exists())->toBeTrue();
});

it('requiert un titre pour créer un article', function () {
    $this->actingAs($this->admin)
        ->post('/admin/blog/articles', ['content' => 'Sans titre'])
        ->assertSessionHasErrors('title');
});

it('affiche la page d édition d un article', function () {
    $article = Article::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/blog/articles/{$article->slug}/edit")
        ->assertOk();
});

it('permet de modifier un article', function () {
    $article = Article::factory()->create();

    $this->actingAs($this->admin)
        ->put("/admin/blog/articles/{$article->slug}", [
            'title' => 'Titre modifié',
            'content' => $article->content,
            'category_id' => $article->category_id,
        ])
        ->assertRedirect();

    expect($article->fresh()->title)->toBe('Titre modifié');
});

it('permet de supprimer un article (soft delete)', function () {
    $article = Article::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/blog/articles/{$article->slug}")
        ->assertRedirect();

    $this->assertSoftDeleted($article);
});

it('permet à un éditeur de créer un article', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->editor)
        ->post('/admin/blog/articles', [
            'title' => 'Article éditeur',
            'content' => 'Contenu éditeur.',
            'category_id' => $category->id,
        ])
        ->assertRedirect();

    expect(Article::where('title->fr', 'Article éditeur')->exists())->toBeTrue();
});

// --- Publier / dépublier ---

it('permet de publier un article brouillon', function () {
    $article = Article::factory()->create(['status' => 'draft']);

    $this->actingAs($this->admin)
        ->post("/admin/blog/articles/{$article->slug}/publish")
        ->assertRedirect();

    expect($article->fresh()->status)->toBeInstanceOf(\Modules\Blog\States\PublishedArticleState::class);
});

it('permet de dépublier un article publié', function () {
    $article = Article::factory()->create(['status' => 'published']);

    $this->actingAs($this->admin)
        ->post("/admin/blog/articles/{$article->slug}/unpublish")
        ->assertRedirect();

    expect($article->fresh()->status)->toBeInstanceOf(\Modules\Blog\States\DraftArticleState::class);
});

// --- Catégories ---

it('permet de créer une catégorie', function () {
    $this->actingAs($this->admin)
        ->post('/admin/blog/categories', ['name' => 'Nouvelle catégorie', 'color' => '#ff0000'])
        ->assertRedirect();

    expect(Category::where('name->fr', 'Nouvelle catégorie')->exists())->toBeTrue();
});

it('empêche la création d une catégorie sans couleur', function () {
    $this->actingAs($this->admin)
        ->post('/admin/blog/categories', ['name' => 'Technologie'])
        ->assertSessionHasErrors('color');
});

it('permet de modifier une catégorie', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->put("/admin/blog/categories/{$category->slug}", ['name' => 'Nouveau', 'color' => '#00ff00'])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Nouveau');
});

it('permet de supprimer une catégorie', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/blog/categories/{$category->slug}")
        ->assertRedirect();

    $this->assertSoftDeleted($category);
});

// --- Commentaires ---

it('permet d approuver un commentaire', function () {
    $article = Article::factory()->create();
    $comment = Comment::factory()->create(['article_id' => $article->id]);

    $this->actingAs($this->admin)
        ->get("/admin/blog/comments/{$comment->id}/approve")
        ->assertRedirect();
});

it('permet de supprimer un commentaire', function () {
    $article = Article::factory()->create();
    $comment = Comment::factory()->create(['article_id' => $article->id]);

    $this->actingAs($this->admin)
        ->delete("/admin/blog/comments/{$comment->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('blog_comments', ['id' => $comment->id]);
});
