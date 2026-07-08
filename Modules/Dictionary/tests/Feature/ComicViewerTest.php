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

it('rend la navigation multi-planches quand une BD a plusieurs pages', function () {
    $comic = ComicLibrary::forSlug('deepfake');

    expect($comic)->toBeArray()
        ->and($comic['planches'])->toHaveCount(2);

    $html = Blade::render('<x-dictionary::comic-viewer :comic="$comic" />', ['comic' => $comic]);

    expect($html)->toContain('cbd-nav')
        ->and($html)->toContain('planches.length > 1')
        ->and($html)->toContain('pageIndex')
        ->and($html)->toContain('bd-deepfake-p1-site')
        ->and($html)->toContain('bd-deepfake-p2-site');
});

it('expose hasBd dans le JSON des termes et intègre le filtre « Avec BD » à l’index', function () {
    $view = file_get_contents(
        module_path('Dictionary', 'resources/views/public/index.blade.php')
    );

    // hasBd exposé pour chaque terme (badge + filtre client).
    expect($view)->toContain("'hasBd' => \\Modules\\Dictionary\\Support\\ComicLibrary::hasComic");

    // Compteur serveur des termes possédant une BD.
    expect($view)->toContain('$bdCount = $termsJson->where(\'hasBd\', true)->count();');

    // État Alpine + intégration à la logique de filtrage existante (pas un 2e système).
    expect($view)->toContain('bdOnly: false')
        ->and($view)->toContain('const matchBd = !this.bdOnly || t.hasBd === true;')
        ->and($view)->toContain('matchSearch && matchType && matchLetter && matchCat && matchBd');

    // Contrôle de bascule rendu + accessibilité (aria-pressed) + chip d’état.
    expect($view)->toContain('@click="bdOnly = !bdOnly"')
        ->and($view)->toContain(':aria-pressed="bdOnly')
        ->and($view)->toContain('{{ __(\'Avec BD\') }}');
});
