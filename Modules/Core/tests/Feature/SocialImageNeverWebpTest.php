<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Blog\Models\Article;
use Modules\Core\Services\SocialImageResolver;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Verrou du correctif 2026-08-22 : ni Facebook ni LinkedIn n'affichent le WebP en aperçu de
 * partage (non documenté chez l'un ni l'autre ; seul X le supporte), et l'AVIF n'est supporté
 * nulle part. Une og:image en WebP/AVIF ne produit AUCUNE erreur - l'aperçu est simplement vide.
 * Ce fichier verrouille SocialImageResolver::shareable() (le point de repli central appelé par
 * Glossaire/Actualités/Blogue/Outils) ainsi que son branchement réel dans la vue du blogue.
 */
function sirTouchFile(string $relativePath): string
{
    $fullPath = public_path($relativePath);
    File::ensureDirectoryExists(dirname($fullPath));
    File::put($fullPath, 'fixture-test-social-image-resolver');

    return $fullPath;
}

afterEach(function () {
    // Nettoyage : ce dossier de test n'existe que pour la durée de cette suite.
    File::deleteDirectory(public_path('images/_test_social_image_resolver'));
});

it('remplace un .webp local par son .jpg jumeau quand il existe', function () {
    $dir = 'images/_test_social_image_resolver/'.uniqid('jpg_');
    sirTouchFile("{$dir}/photo.jpg");

    expect(SocialImageResolver::shareable("{$dir}/photo.webp"))->toBe("{$dir}/photo.jpg");
});

it('replie sur le .png jumeau quand seul lui existe', function () {
    $dir = 'images/_test_social_image_resolver/'.uniqid('png_');
    sirTouchFile("{$dir}/photo.png");

    expect(SocialImageResolver::shareable("{$dir}/photo.webp"))->toBe("{$dir}/photo.png");
});

it('replie sur le visuel par defaut quand un .webp local n\'a ni .jpg ni .png jumeau - jamais le .webp', function () {
    $dir = 'images/_test_social_image_resolver/'.uniqid('none_');

    $result = SocialImageResolver::shareable("{$dir}/photo.webp");

    expect($result)->toBe('images/og-image.png')
        ->not->toContain('.webp');
});

it('replie aussi un .avif local sans jumeau', function () {
    $dir = 'images/_test_social_image_resolver/'.uniqid('avif_');

    expect(SocialImageResolver::shareable("{$dir}/photo.avif"))->toBe('images/og-image.png');
});

it('laisse un chemin .jpg local inchange', function () {
    $dir = 'images/_test_social_image_resolver/'.uniqid('unchanged_');

    expect(SocialImageResolver::shareable("{$dir}/photo.jpg"))->toBe("{$dir}/photo.jpg");
});

it('replie une URL externe en .webp sur le visuel par defaut - on ne peut pas la convertir', function () {
    expect(SocialImageResolver::shareable('https://cdn.example.com/photo.webp'))
        ->toBe('images/og-image.png');
});

it('laisse une URL externe en .jpg inchangee', function () {
    $url = 'https://cdn.example.com/photo.jpg';

    expect(SocialImageResolver::shareable($url))->toBe($url);
});

it('replie une valeur vide ou nulle sur le visuel par defaut', function () {
    expect(SocialImageResolver::shareable(null))->toBe('images/og-image.png');
    expect(SocialImageResolver::shareable(''))->toBe('images/og-image.png');
    expect(SocialImageResolver::shareable('   '))->toBe('images/og-image.png');
});

it('accepte un visuel de repli personnalise', function () {
    expect(SocialImageResolver::shareable(null, 'images/repli-outil.png'))
        ->toBe('images/repli-outil.png');
});

it("l'accesseur Article::featured_image_shareable_url ne retourne jamais de .webp", function () {
    $article = Article::factory()->make([
        'featured_image' => 'articles/sans-jumeau-'.uniqid().'.webp',
    ]);

    expect($article->featured_image_shareable_url)
        ->toContain('og-image.png')
        ->not->toContain('.webp');
});

it('integration : la vue du blogue ne sert jamais og:image en .webp', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Article de verrouillage anti-WebP',
        'slug' => 'article-anti-webp-'.uniqid(),
        'featured_image' => 'articles/sans-jumeau-'.uniqid().'.webp',
    ]);

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    expect($html)->toContain('property="og:image"');

    preg_match('/property="og:image" content="([^"]*)"/', $html, $matches);

    expect($matches[1] ?? null)
        ->not()->toBeNull()
        ->not()->toContain('.webp')
        ->toContain('og-image.png');
});

it("l'accesseur Article::featured_image_shareable_url sert l'URL publique correcte pour un chemin à slash initial existant sous public/", function () {
    // 2026-09-02 : même correctif que featured_image_url (voir ArticleFeaturedImageUrlTest) -
    // avant, un chemin à slash initial (ex. /images/blog/{slug}.jpg écrit par /actu2) ressortait
    // en "storage//images/blog/x.jpg" (préfixe erroné + double barre oblique), 404 chez l'aperçu
    // Facebook/LinkedIn alors que le fichier existe bel et bien sous public/.
    $dir = 'images/_test_social_image_resolver/'.uniqid('shareable_slash_');
    sirTouchFile("{$dir}/photo.jpg");

    $article = Article::factory()->make(['featured_image' => "/{$dir}/photo.jpg"]);

    expect($article->featured_image_shareable_url)
        ->toContain("{$dir}/photo.jpg")
        ->not->toContain('/storage/')
        ->not->toContain('og-image.png');
});

it("l'accesseur Article::featured_image_shareable_url conserve le repli générique quand un chemin à slash initial est réellement absent", function () {
    $article = Article::factory()->make([
        'featured_image' => '/images/_test_social_image_resolver/fantome-'.uniqid().'/photo.jpg',
    ]);

    expect($article->featured_image_shareable_url)->toContain('og-image.png');
});
