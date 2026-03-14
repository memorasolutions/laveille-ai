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
    $this->admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

// ── Catégories ────────────────────────────────────────────────────────────────

it('categories index loads', function () {
    $this->get(route('admin.blog.categories.index'))->assertStatus(200);
});

it('categories create page loads', function () {
    $this->get(route('admin.blog.categories.create'))->assertStatus(200);
});

it('stores a category', function () {
    $this->post(route('admin.blog.categories.store'), [
        'name' => 'Laravel',
        'color' => '#6366f1',
        'is_active' => '1',
    ])->assertRedirect(route('admin.blog.categories.index'));

    expect(Category::where('name->'.app()->getLocale(), 'Laravel')->exists())->toBeTrue();
});

it('updates a category', function () {
    $category = Category::factory()->create();

    $this->put(route('admin.blog.categories.update', $category), [
        'name' => 'Updated',
        'color' => '#ff0000',
    ])->assertRedirect(route('admin.blog.categories.index'));

    expect($category->fresh()->name)->toBe('Updated');
});

it('deletes a category', function () {
    $category = Category::factory()->create();

    $this->delete(route('admin.blog.categories.destroy', $category))
        ->assertRedirect(route('admin.blog.categories.index'));

    expect(Category::find($category->id))->toBeNull();
});

it('non-admin gets 403 on categories index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.blog.categories.index'))
        ->assertStatus(403);
});

// ── Commentaires ──────────────────────────────────────────────────────────────

it('comments index loads', function () {
    $this->get(route('admin.blog.comments.index'))->assertStatus(200);
});

it('approves a comment', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $comment = Comment::factory()->create([
        'article_id' => $article->id,
        'status' => 'pending',
    ]);

    $this->get(route('admin.blog.comments.approve', $comment))
        ->assertRedirect();

    expect((string) $comment->fresh()->status)->toBe('approved');
});

it('force-deletes a comment', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id]);
    $comment = Comment::factory()->create(['article_id' => $article->id]);

    $this->delete(route('admin.blog.comments.destroy', $comment))
        ->assertRedirect();

    expect(Comment::withTrashed()->find($comment->id))->toBeNull();
});
