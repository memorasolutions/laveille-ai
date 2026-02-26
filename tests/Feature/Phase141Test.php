<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleRevision;
use Modules\Blog\Services\ArticleRevisionService;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

test('article_revisions table exists', function () {
    expect(Schema::hasTable('article_revisions'))->toBeTrue();
});

test('ArticleRevision model class exists', function () {
    expect(class_exists(ArticleRevision::class))->toBeTrue();
});

test('revision created when article title updated', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Nouveau titre']);

    expect($article->revisions()->count())->toBe(1);
});

test('revision stores original title before update', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'title' => 'Ancien titre', 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Nouveau titre']);
    $revision = $article->revisions()->first();

    expect($revision->title)->toContain('Ancien titre');
});

test('revision number increments correctly', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Update 1']);
    $article->update(['title' => 'Update 2']);

    $revisions = $article->revisions()->reorder('revision_number', 'asc')->get();
    expect($revisions)->toHaveCount(2);
    expect((int) $revisions[0]->revision_number)->toBeLessThan((int) $revisions[1]->revision_number);
});

test('no revision if non-tracked field changes', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $article->update(['featured_image' => 'new-image.jpg']);

    expect($article->revisions()->count())->toBe(0);
});

test('admin can access revisions list page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $article = Article::factory()->create(['user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.blog.articles.revisions', $article))
        ->assertOk();
});

test('guest redirected from revisions page', function () {
    $article = Article::factory()->create();

    $this->get(route('admin.blog.articles.revisions', $article))
        ->assertRedirect();
});

test('service restore updates article with revision data', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'title' => 'Original', 'content' => 'Contenu original', 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Modifié', 'content' => 'Contenu modifié']);
    $revision = $article->revisions()->first();

    $service = app(ArticleRevisionService::class);
    $service->restore($article, $revision);

    $article->refresh();
    expect($article->title)->toContain('Original');
});

test('restore creates new revision from the update', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Modifié']);
    $revision = $article->revisions()->first();
    $countBefore = $article->revisions()->count();

    $service = app(ArticleRevisionService::class);
    $service->restore($article, $revision);

    expect($article->revisions()->count())->toBe($countBefore + 1);
});

test('revisions relation returns descending order', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Update 1']);
    $article->update(['title' => 'Update 2']);

    $revisions = $article->revisions;
    expect($revisions->first()->revision_number)->toBe(2);
    expect($revisions->last()->revision_number)->toBe(1);
});

test('deleting article force delete cascades revisions', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user);
    $article->update(['title' => 'Modifié']);
    $articleId = $article->id;

    $article->forceDelete();

    expect(ArticleRevision::where('article_id', $articleId)->count())->toBe(0);
});

test('ArticleRevisionService is resolvable from container', function () {
    expect(app(ArticleRevisionService::class))->toBeInstanceOf(ArticleRevisionService::class);
});
