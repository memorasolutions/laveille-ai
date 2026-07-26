<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('returns null when featured_image is empty', function () {
    $article = Article::factory()->make(['featured_image' => null]);

    expect($article->featured_image_url)->toBeNull();
});

it('returns the asset url for an existing storage/-prefixed file', function () {
    $article = Article::factory()->make(['featured_image' => 'storage/blog/does-not-exist-'.uniqid().'.jpg']);

    expect($article->featured_image_url)->toContain('images/og-image.png');
});

it('falls back to the default og image when the storage/-prefixed file does not exist on disk', function () {
    // 2026-07-25 #1298 : régression réelle en prod - 12 articles Concentré avaient un
    // featured_image jamais téléversé, produisant une <img> cassée. Verrouille le repli.
    $article = Article::factory()->make(['featured_image' => 'images/blog/concentre-hebdo-fantome.jpg']);

    expect($article->featured_image_url)->toContain('images/og-image.png');
});

it('returns the public disk url when the public-disk file actually exists', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::disk('public')->put('blog/real-image.jpg', 'fake-content');

    $article = Article::factory()->make(['featured_image' => 'blog/real-image.jpg']);

    expect($article->featured_image_url)->toContain('blog/real-image.jpg')
        ->not->toContain('og-image.png');
});
