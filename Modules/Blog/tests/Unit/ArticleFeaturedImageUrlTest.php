<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

afterEach(function () {
    // Nettoyage : ce dossier de test n'existe que pour la durée de cette suite - jamais
    // public/images/blog/ lui-même (un autre chantier y écrit en parallèle).
    File::deleteDirectory(public_path('images/_test_article_featured_image'));
});

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

it('sert l\'URL publique correcte pour un chemin à slash initial dont le fichier existe réellement sous public/', function () {
    // Défaut mesuré en production le 2026-09-02 : /images/blog/{slug}.jpg (écrit par le
    // pipeline d'illustration /actu2) répond 200 sur le serveur, mais aucune des trois
    // branches historiques de l'accesseur ne le reconnaissait - l'affichage retombait à tort
    // sur l'image générique. Fixture sous un dossier de test dédié (jamais public/images/blog/
    // lui-même, un autre agent y travaille en parallèle) pour ne pas dépendre d'un vrai fichier
    // de production.
    $dir = 'images/_test_article_featured_image/'.uniqid('exists_');
    $fullPath = public_path("{$dir}/photo.jpg");
    File::ensureDirectoryExists(dirname($fullPath));
    File::put($fullPath, 'fixture-test-article-featured-image');

    $article = Article::factory()->make(['featured_image' => "/{$dir}/photo.jpg"]);

    expect($article->featured_image_url)
        ->toContain("{$dir}/photo.jpg")
        ->not->toContain('og-image.png')
        ->not->toContain('/storage/');
});

it('conserve le repli générique quand un chemin à slash initial est réellement absent (non-régression)', function () {
    // Le correctif ci-dessus ne doit JAMAIS faire disparaître le repli du 2026-07-25 #1298 :
    // un chemin à slash initial dont le fichier n'existe nulle part doit toujours retomber sur
    // l'image générique, pas produire une <img> cassée.
    $article = Article::factory()->make([
        'featured_image' => '/images/_test_article_featured_image/fantome-'.uniqid().'/photo.jpg',
    ]);

    expect($article->featured_image_url)->toContain('images/og-image.png');
});
