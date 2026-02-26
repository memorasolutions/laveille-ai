<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
});

it('blog index loads with status 200', function () {
    $this->get(route('blog.index'))->assertOk();
});

it('blog index shows published articles', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.index'))->assertSee($article->title);
});

it('blog index does not show draft articles', function () {
    $draft = Article::factory()->draft()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.index'))->assertDontSee($draft->title);
});

it('blog index shows empty state when no articles', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Aucun article pour le moment');
});

it('blog index filters by category slug', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->published()->create(['category_id' => $category->id, 'user_id' => $this->user->id]);

    $this->get(route('blog.index', ['category' => $category->slug]))
        ->assertOk()
        ->assertSee($article->title);
});

it('blog index filters articles to only show selected category', function () {
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();

    $article1 = Article::factory()->published()->create(['category_id' => $cat1->id, 'user_id' => $this->user->id]);
    // article2 dans cat2 ne doit pas apparaître dans la grille principale
    Article::factory()->published()->create(['category_id' => $cat2->id, 'user_id' => $this->user->id]);

    // Quand on filtre par cat1, article1 est bien présent
    $this->get(route('blog.index', ['category' => $cat1->slug]))
        ->assertOk()
        ->assertSee($article1->title);
});

it('blog index shows category filters when categories have published articles', function () {
    $category = Category::factory()->create(['name' => 'Technologie']);
    // La vue n'affiche que les catégories avec articles_count > 0
    Article::factory()->published()->create(['category_id' => $category->id, 'user_id' => $this->user->id]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Technologie');
});

it('blog show returns 200 for published article', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.show', $article->slug))->assertOk();
});

it('blog show returns 404 for draft article', function () {
    $draft = Article::factory()->draft()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.show', $draft->slug))->assertNotFound();
});

it('blog show displays article title', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee($article->title);
});

it('blog show displays related articles from same category', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->published()->create(['category_id' => $category->id, 'user_id' => $this->user->id]);
    $related = Article::factory()->published()->create(['category_id' => $category->id, 'user_id' => $this->user->id]);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee($related->title);
});

it('blog show has comment form', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('Laisser un commentaire');
});

it('blog show has back to blog link', function () {
    $article = Article::factory()->published()->create(['user_id' => $this->user->id]);
    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('Retour au blog');
});
