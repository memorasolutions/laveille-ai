<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create();
    // ArticleSubmissionController::store() hardcode user_id=1 (systeme/auteur par defaut) -
    // garantir son existence pour ne pas violer la contrainte de cle etrangere en test.
    \App\Models\User::factory()->create(['id' => 1]);
    $this->user = \App\Models\User::factory()->create();
});

it('purifie le contenu soumis pour neutraliser une XSS stockee (script)', function () {
    $malicious = str_repeat('Texte legitime. ', 15).'<script>alert(document.cookie)</script><h2>Titre</h2>';

    $response = $this->actingAs($this->user)->post(route('blog.submissions.store'), [
        'title' => 'Article de test securite',
        'content' => $malicious,
        'category_id' => $this->category->id,
        'author_bio' => str_repeat('Bio legitime. ', 10),
        'sources' => 'https://example.com/source',
        'excerpt' => '',
    ]);

    $response->assertSessionDoesntHaveErrors();

    $article = Article::latest('id')->first();

    expect($article)->not->toBeNull();
    expect($article->getTranslation('content', 'fr'))
        ->not->toContain('<script>')
        ->not->toContain('alert(')
        ->toContain('<h2>Titre</h2>');
});

it('purifie une injection via onerror et javascript: URI', function () {
    $malicious = str_repeat('Texte legitime. ', 15).'<img src=x onerror="alert(1)"><a href="javascript:alert(2)">lien</a>';

    $this->actingAs($this->user)->post(route('blog.submissions.store'), [
        'title' => 'Article de test securite 2',
        'content' => $malicious,
        'category_id' => $this->category->id,
        'author_bio' => str_repeat('Bio legitime. ', 10),
        'sources' => 'https://example.com/source',
        'excerpt' => '',
    ]);

    $article = Article::latest('id')->first();
    $content = $article->getTranslation('content', 'fr');

    expect($content)
        ->not->toContain('onerror')
        ->not->toContain('javascript:');
});

it('preserve un contenu legitime sans HTML', function () {
    $legit = str_repeat('Ceci est un article tout a fait normal sans HTML, juste du texte brut. ', 5);

    $this->actingAs($this->user)->post(route('blog.submissions.store'), [
        'title' => 'Article legitime',
        'content' => $legit,
        'category_id' => $this->category->id,
        'author_bio' => str_repeat('Bio legitime. ', 10),
        'sources' => 'https://example.com/source',
        'excerpt' => '',
    ]);

    $article = Article::latest('id')->first();

    expect($article->getTranslation('content', 'fr'))->toContain('Ceci est un article tout a fait normal');
});
