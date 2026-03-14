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
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

test('admin peut accéder à la corbeille', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.trash.index'))
        ->assertOk();
});

test('invité redirigé vers login', function () {
    $this->get(route('admin.trash.index'))
        ->assertRedirect();
});

test('non-admin reçoit 403', function () {
    $this->actingAs($this->user)
        ->get(route('admin.trash.index'))
        ->assertForbidden();
});

test('page affiche le titre Corbeille', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.trash.index'))
        ->assertSee('Corbeille');
});

test('état vide affiche messages', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.trash.index'))
        ->assertSee('Aucun article dans la corbeille')
        ->assertSee('Aucun commentaire dans la corbeille');
});

test('article supprimé apparaît dans la corbeille', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $article->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.trash.index'))
        ->assertSee($article->title);
});

test('article peut être restauré', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $article->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.trash.restore-article', $article->id))
        ->assertRedirect();

    expect(Article::find($article->id))->not->toBeNull();
});

test('article peut être supprimé définitivement', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $article->delete();

    $this->actingAs($this->admin)
        ->delete(route('admin.trash.force-delete-article', $article->id))
        ->assertRedirect();

    expect(Article::withTrashed()->find($article->id))->toBeNull();
});

test('commentaire supprimé apparaît dans la corbeille', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $comment = Comment::factory()->create(['article_id' => $article->id]);
    $comment->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.trash.index'))
        ->assertSee('Commentaires supprimés (1)');
});

test('commentaire peut être restauré', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $comment = Comment::factory()->create(['article_id' => $article->id]);
    $comment->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.trash.restore-comment', $comment->id))
        ->assertRedirect();

    expect(Comment::find($comment->id))->not->toBeNull();
});
