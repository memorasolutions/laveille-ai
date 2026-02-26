<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'super_admin']);

    $this->article = Article::factory()->create([
        'slug' => 'test-article',
        'status' => 'published',
    ]);
});

it('guest peut soumettre un commentaire avec nom et email', function () {
    $response = $this->post("/blog/{$this->article->slug}/comments", [
        'guest_name' => 'Jean Dupont',
        'guest_email' => 'jean@example.com',
        'content' => 'Ceci est un commentaire invité.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_comments', [
        'article_id' => $this->article->id,
        'guest_name' => 'Jean Dupont',
        'content' => 'Ceci est un commentaire invité.',
        'status' => 'pending',
        'user_id' => null,
    ]);
});

it('utilisateur connecté peut soumettre un commentaire', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post("/blog/{$this->article->slug}/comments", [
        'content' => 'Commentaire utilisateur connecté.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_comments', [
        'article_id' => $this->article->id,
        'user_id' => $user->id,
        'content' => 'Commentaire utilisateur connecté.',
        'status' => 'pending',
    ]);
});

it('commentaire est en statut pending par défaut', function () {
    $comment = Comment::factory()->create(['article_id' => $this->article->id]);
    expect((string) $comment->status)->toBe('pending');
});

it('commentaire non approuvé est invisible sur la page article', function () {
    $comment = Comment::factory()->create([
        'article_id' => $this->article->id,
        'status' => 'pending',
        'content' => 'Commentaire en attente SECRET',
    ]);

    $response = $this->get("/blog/{$this->article->slug}");
    $response->assertOk();
    $response->assertDontSee('Commentaire en attente SECRET');
});

it('commentaire approuvé est visible sur la page article', function () {
    $comment = Comment::factory()->create([
        'article_id' => $this->article->id,
        'status' => 'approved',
        'content' => 'Commentaire approuvé PUBLIC',
    ]);

    $response = $this->get("/blog/{$this->article->slug}");
    $response->assertOk();
    $response->assertSee('Commentaire approuvé PUBLIC');
});

it('admin peut approuver un commentaire', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $comment = Comment::factory()->create([
        'article_id' => $this->article->id,
        'status' => 'pending',
    ]);

    $response = $this->get("/admin/blog/comments/{$comment->id}/approve");
    $response->assertRedirect();

    expect((string) $comment->fresh()->status)->toBe('approved');
});

it('admin peut supprimer un commentaire définitivement', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $comment = Comment::factory()->create(['article_id' => $this->article->id]);

    $response = $this->delete("/admin/blog/comments/{$comment->id}");
    $response->assertRedirect();

    $this->assertDatabaseMissing('blog_comments', ['id' => $comment->id, 'deleted_at' => null]);
});

it('validation échoue si content est vide', function () {
    $response = $this->post("/blog/{$this->article->slug}/comments", [
        'guest_name' => 'Jean',
        'guest_email' => 'jean@example.com',
        'content' => '',
    ]);

    $response->assertSessionHasErrors('content');
});

it('validation échoue si invité sans email', function () {
    $response = $this->post("/blog/{$this->article->slug}/comments", [
        'guest_name' => 'Jean',
        'content' => 'Un beau commentaire sans email.',
    ]);

    $response->assertSessionHasErrors('guest_email');
});

it('la page article affiche la section commentaires', function () {
    $response = $this->get("/blog/{$this->article->slug}");
    $response->assertOk();
    $response->assertSee('Commentaires');
});
