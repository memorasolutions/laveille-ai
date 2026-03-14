<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleRevision;
use Modules\Blog\Services\ArticleRevisionService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->user = User::factory()->create();
    $this->article = Article::factory()->create(['user_id' => $this->admin->id, 'status' => 'draft']);
});

// --- Model relationships ---

it('revision belongs to article', function () {
    $revision = ArticleRevision::factory()->create(['article_id' => $this->article->id]);

    expect($revision->article)->toBeInstanceOf(Article::class)
        ->and($revision->article->id)->toBe($this->article->id);
});

it('revision belongs to user', function () {
    $revision = ArticleRevision::factory()->create(['user_id' => $this->admin->id]);

    expect($revision->user)->toBeInstanceOf(User::class)
        ->and($revision->user->id)->toBe($this->admin->id);
});

it('article has many revisions', function () {
    ArticleRevision::factory()->count(3)->create(['article_id' => $this->article->id]);

    expect($this->article->revisions)->toHaveCount(3);
});

// --- ArticleRevisionService ---

it('createRevision creates revision when tracked fields change', function () {
    $this->actingAs($this->admin);
    $this->article->update(['title' => 'Nouveau titre modifié']);

    // Observer auto-creates revision on update
    $revision = $this->article->revisions()->first();

    expect($revision)->toBeInstanceOf(ArticleRevision::class)
        ->and($revision->revision_number)->toBe(1);
});

it('createRevision returns null when no tracked fields changed', function () {
    $this->actingAs($this->admin);
    $this->article->update(['category' => 'test-non-tracked']);

    $service = new ArticleRevisionService;
    $revision = $service->createRevision($this->article);

    expect($revision)->toBeNull();
});

it('createRevision increments revision number', function () {
    $this->actingAs($this->admin);
    ArticleRevision::factory()->create(['article_id' => $this->article->id, 'revision_number' => 3]);

    // Observer creates revision on update (next number after 3 = 4)
    $this->article->update(['title' => 'Titre modifié']);
    $revision = $this->article->revisions()->orderByDesc('revision_number')->first();

    expect($revision->revision_number)->toBe(4);
});

it('createRevision stores original values before change', function () {
    $this->actingAs($this->admin);
    // Observer auto-creates revision with original values
    $this->article->update(['content' => 'Contenu totalement différent']);
    $revision = $this->article->revisions()->first();

    // Revision content should NOT be the new value
    expect($revision->content)->not->toBe('Contenu totalement différent');
});

it('restore updates article with revision data', function () {
    $revision = ArticleRevision::factory()->create([
        'article_id' => $this->article->id,
        'title' => 'Ancien titre restauré',
        'content' => 'Ancien contenu restauré',
        'excerpt' => 'Ancien extrait',
    ]);

    $service = new ArticleRevisionService;
    $restored = $service->restore($this->article, $revision);

    expect($restored->title)->toBe('Ancien titre restauré')
        ->and($restored->content)->toBe('Ancien contenu restauré');
});

it('getRevisions returns max 20 by default', function () {
    ArticleRevision::factory()->count(25)->create(['article_id' => $this->article->id]);

    $service = new ArticleRevisionService;
    $revisions = $service->getRevisions($this->article);

    expect($revisions)->toHaveCount(20);
});

it('getRevisions respects custom limit', function () {
    ArticleRevision::factory()->count(15)->create(['article_id' => $this->article->id]);

    $service = new ArticleRevisionService;
    $revisions = $service->getRevisions($this->article, 5);

    expect($revisions)->toHaveCount(5);
});

// --- Routes admin ---

it('admin can view revisions index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.revisions', $this->article))
        ->assertOk();
});

it('admin can view revision show', function () {
    $revision = ArticleRevision::factory()->create(['article_id' => $this->article->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.revisions.show', [$this->article, $revision]))
        ->assertOk();
});

it('admin can restore a revision', function () {
    $revision = ArticleRevision::factory()->create([
        'article_id' => $this->article->id,
        'title' => 'Titre à restaurer',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.revisions.restore', [$this->article, $revision]))
        ->assertRedirect();

    expect($this->article->fresh()->title)->toBe('Titre à restaurer');
});

it('non-admin cannot access revisions index', function () {
    $this->actingAs($this->user)
        ->get(route('admin.blog.articles.revisions', $this->article))
        ->assertForbidden();
});

it('non-admin cannot restore revision', function () {
    $revision = ArticleRevision::factory()->create(['article_id' => $this->article->id]);

    $this->actingAs($this->user)
        ->post(route('admin.blog.articles.revisions.restore', [$this->article, $revision]))
        ->assertForbidden();
});

it('guest is redirected to login', function () {
    $this->get(route('admin.blog.articles.revisions', $this->article))
        ->assertRedirect('/login');
});

// --- Diff ---

it('admin can view diff page', function () {
    $revision = ArticleRevision::factory()->create(['article_id' => $this->article->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.revisions.diff', [$this->article, $revision]))
        ->assertOk()
        ->assertSee(__('Comparaison'));
});

it('diff service produces correct output', function () {
    $service = new \Modules\Blog\Services\DiffService;
    $result = $service->diffHtml('hello world', 'hello beautiful world');

    expect($result)->toContain('hello')
        ->and($result)->toContain('<ins')
        ->and($result)->toContain('beautiful');
});

it('diff service handles identical text', function () {
    $service = new \Modules\Blog\Services\DiffService;
    $result = $service->diff('same text', 'same text');

    $types = array_column($result, 'type');
    expect($types)->each->toBe('unchanged');
});

it('diff service handles removed text', function () {
    $service = new \Modules\Blog\Services\DiffService;
    $result = $service->diffHtml('hello world foo', 'hello world');

    expect($result)->toContain('<del')
        ->and($result)->toContain('foo');
});
