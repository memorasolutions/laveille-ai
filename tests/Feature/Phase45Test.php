<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;

uses(RefreshDatabase::class);

it('GET /api/v1/articles retourne 200 avec clé data', function () {
    Article::factory()->published()->create();

    $this->getJson('/api/v1/articles')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('filtre par category fonctionne', function () {
    Article::factory()->published()->create(['category' => 'tech']);
    Article::factory()->published()->create(['category' => 'design']);

    $response = $this->getJson('/api/v1/articles?category=tech');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.category', 'tech');
});

it('GET /api/v1/articles/{slug} retourne 200 si publié', function () {
    $article = Article::factory()->published()->create();

    $this->getJson("/api/v1/articles/{$article->slug}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['article', 'comments']]);
});

it('GET /api/v1/articles/{slug} retourne 404 si draft', function () {
    $article = Article::factory()->create(['status' => 'draft']);

    $this->getJson("/api/v1/articles/{$article->slug}")
        ->assertNotFound();
});

it('GET /api/v1/blog/categories retourne 200 avec tableau', function () {
    Article::factory()->published()->create(['category' => 'tech']);
    Article::factory()->published()->create(['category' => 'design']);

    $this->getJson('/api/v1/blog/categories')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('POST /api/v1/newsletter/subscribe avec email valide retourne 201', function () {
    $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'test@example.com'])
        ->assertCreated()
        ->assertJsonPath('message', 'Abonné avec succès');
});

it('POST /api/v1/newsletter/subscribe sans email retourne 422', function () {
    $this->postJson('/api/v1/newsletter/subscribe', [])
        ->assertUnprocessable();
});

it('GET /api/v1/status retourne 200', function () {
    $this->getJson('/api/v1/status')
        ->assertOk()
        ->assertJsonPath('version', 'v1');
});

it('articles API includes author relation', function () {
    $article = Article::factory()->published()->create();

    $response = $this->getJson('/api/v1/articles');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['author' => ['id', 'name']]]]);
});

it('articles API includes comments_count', function () {
    $article = Article::factory()->published()->create();
    Comment::factory()->count(3)->approved()->create(['article_id' => $article->id]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertOk()
        ->assertJsonPath('data.0.comments_count', 3);
});

it('article show includes comments list', function () {
    $article = Article::factory()->published()->create();
    Comment::factory()->count(2)->approved()->create(['article_id' => $article->id]);

    $response = $this->getJson("/api/v1/articles/{$article->slug}");

    $response->assertOk()
        ->assertJsonCount(2, 'data.comments');
});

it('article show includes author details', function () {
    $article = Article::factory()->published()->create();

    $response = $this->getJson("/api/v1/articles/{$article->slug}");

    $response->assertOk()
        ->assertJsonStructure(['data' => ['article' => ['author' => ['id', 'name']]]]);
});
