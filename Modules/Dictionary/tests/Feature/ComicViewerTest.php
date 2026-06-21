<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Modules\Dictionary\Support\ComicLibrary;

uses(Tests\TestCase::class);

it('détecte une BD présente par convention (manifest.json)', function () {
    expect(ComicLibrary::hasComic('cheval-de-troie'))->toBeTrue();
});

it('ne détecte pas de BD pour un terme sans manifest', function () {
    expect(ComicLibrary::hasComic('terme-sans-bd-inexistant-xyz'))->toBeFalse();
});

it('ignore les slugs invalides (anti-traversal)', function () {
    expect(ComicLibrary::hasComic('../etc/passwd'))->toBeFalse();
    expect(ComicLibrary::hasComic(''))->toBeFalse();
});

it('résout le manifest avec URL absolues et métadonnées', function () {
    $comic = ComicLibrary::forSlug('cheval-de-troie');

    expect($comic)->toBeArray()
        ->and($comic['term_slug'])->toBe('cheval-de-troie')
        ->and($comic['title'])->not->toBe('')
        ->and($comic['alt'])->not->toBe('')
        ->and($comic['planches'])->toHaveCount(1);

    $planche = $comic['planches'][0];
    expect($planche['jpg'])->toContain('/bd/cheval-de-troie/')
        ->and($planche['jpg'])->toContain('.jpg')
        ->and($planche['avif'])->toContain('.avif')
        ->and($planche['webp'])->toContain('.webp')
        ->and($planche['thumb'])->toContain('thumb')
        ->and($planche['width'])->toBe(1600)
        ->and($planche['height'])->toBe(2448)
        ->and($comic['download_url'])->toContain('.jpg');
});

it('renvoie null pour un terme sans BD', function () {
    expect(ComicLibrary::forSlug('terme-sans-bd-inexistant-xyz'))->toBeNull();
});

it('rend le bouton « Lire la BD » et le viewer quand une BD existe', function () {
    $comic = ComicLibrary::forSlug('cheval-de-troie');

    $html = Blade::render('<x-dictionary::comic-viewer :comic="$comic" />', ['comic' => $comic]);

    expect($html)->toContain('Lire la BD')
        ->and($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('/bd/cheval-de-troie/')
        ->and($html)->toContain('Télécharger');
});

it('ne rend aucun viewer quand il n’y a pas de BD', function () {
    $html = Blade::render('<x-dictionary::comic-viewer :comic="$comic" />', ['comic' => null]);

    expect(trim($html))->not->toContain('Lire la BD')
        ->and(trim($html))->not->toContain('role="dialog"');
});
