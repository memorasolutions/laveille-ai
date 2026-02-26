<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

it('le flux RSS retourne 200 et du XML valide', function () {
    Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/blog/feed.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    expect($response->getContent())->toContain('<rss');
    expect($response->getContent())->toContain('<channel');
});

it('le flux RSS contient les articles publiés', function () {
    $article = Article::factory()->create([
        'title' => 'Mon article RSS',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/blog/feed.xml');

    expect($response->getContent())->toContain('Mon article RSS');
});

it('la page FAQ retourne 200', function () {
    $this->get('/faq')->assertStatus(200);
});

it('la page FAQ affiche les questions', function () {
    $response = $this->get('/faq');
    $response->assertSee('Questions fréquentes');
    $response->assertSee('Laravel SaaS');
});
