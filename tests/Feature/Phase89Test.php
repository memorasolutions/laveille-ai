<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
});

it('blog index filters by tag shows tagged article', function () {
    $article = Article::factory()->published()->create(['tags' => ['laravel', 'php'], 'user_id' => $this->user->id]);
    $this->get(route('blog.index', ['tag' => 'laravel']))
        ->assertOk()
        ->assertSee($article->title);
});

it('blog index tag filter is applied', function () {
    $article = Article::factory()->published()->create(['tags' => ['laravel'], 'user_id' => $this->user->id]);
    Article::factory()->published()->create(['tags' => ['vue'], 'user_id' => $this->user->id]);
    $this->get(route('blog.index', ['tag' => 'laravel']))
        ->assertOk()
        ->assertSee($article->title);
});

it('blog index without tag filter shows all published articles', function () {
    $a1 = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $a2 = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.index'))
        ->assertSee($a1->title)
        ->assertSee($a2->title);
});

it('author page loads for existing user', function () {
    $this->get(route('blog.author', $this->user))->assertOk();
});

it('author page shows user name', function () {
    $this->user->update(['name' => 'Jean Auteur']);
    $this->get(route('blog.author', $this->user))->assertSee('Jean Auteur');
});

it('author page shows published articles of author', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.author', $this->user))->assertSee($article->title);
});

it('author page hides draft articles', function () {
    $draft = Article::factory()->draft()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.author', $this->user))->assertDontSee($draft->title);
});

it('author page shows empty state when no published articles', function () {
    $this->get(route('blog.author', $this->user))
        ->assertOk()
        ->assertSee('Aucun article');
});

it('author page has back to blog link', function () {
    $this->get(route('blog.author', $this->user))->assertSee('Blog');
});

it('author page shows total articles count', function () {
    Article::factory()->published()->create(['user_id' => $this->user->id]);
    Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.author', $this->user))->assertSee('2');
});

it('article show page has author link', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee(route('blog.author', $this->user), false);
});

it('article show tags are clickable links', function () {
    $article = Article::factory()->published()->create(['tags' => ['laravel', 'php'], 'user_id' => $this->user->id]);
    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('?tag=laravel', false);
});

it('blog index sidebar tags are clickable links', function () {
    Article::factory()->published()->create(['tags' => ['laravel'], 'user_id' => $this->user->id]);
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('?tag=laravel', false);
});
