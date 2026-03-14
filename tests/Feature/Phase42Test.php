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

it('commentaire est en statut pending par défaut', function () {
    $comment = Comment::factory()->create(['article_id' => $this->article->id]);
    expect((string) $comment->status)->toBe('pending');
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
