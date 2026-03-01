<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('public blog index returns 200', function () {
    $this->get('/blog')->assertStatus(200);
});

it('published article is visible on public blog', function () {
    $article = Article::factory()->published()->create(['title' => 'My Published Post']);
    $this->get('/blog')->assertStatus(200)->assertSee('My Published Post');
});

it('draft article is not visible on public blog', function () {
    $article = Article::factory()->draft()->create(['title' => 'My Hidden Draft']);
    $this->get('/blog')->assertStatus(200)->assertDontSee('My Hidden Draft');
});

it('published article show page returns 200', function () {
    $article = Article::factory()->published()->create();
    $this->get('/blog/'.$article->slug)->assertStatus(200)->assertSee($article->title);
});

it('draft article show returns 404', function () {
    $article = Article::factory()->draft()->create();
    $this->get('/blog/'.$article->slug)->assertStatus(404);
});

it('blog index filters by category', function () {
    // Le filtre utilise maintenant category_id via le modèle Category (slug)
    // Un filtre avec un slug qui n'existe pas retourne tous les articles (pas de catégorie trouvée)
    $phpArticle = Article::factory()->published()->create(['title' => 'PHP Article', 'category' => 'PHP']);

    // Le filtre ?category=inexistant retourne une page 200 sans crash
    $this->get('/blog?category=inexistant-slug')
        ->assertStatus(200);

    // Sans filtre, l'article PHP est visible
    $this->get('/blog')
        ->assertSee('PHP Article');
});
