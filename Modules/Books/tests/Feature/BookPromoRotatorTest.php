<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai - rotation des encarts publicitaires internes
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Books\Models\Book;
use Modules\Books\Services\BookPromoRotator;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    BookPromoRotator::resetServed();
    BookPromoRotator::clearContext();
});

afterEach(function () {
    // Sans ça, la date figée du dernier test fuirait dans toute la suite.
    Carbon::setTestNow();
    BookPromoRotator::resetServed();
    BookPromoRotator::clearContext();
});

it('met en avant les trois livres retenus, dans l\'ordre du cycle', function () {
    expect(BookPromoRotator::pool())->toBe([
        'ia-sans-se-faire-poursuivre',
        'ia-pour-les-parents',
        'nexus-neural-tome-1',
    ]);
});

it('choisit toujours un livre qui fait partie du cycle', function () {
    BookPromoRotator::setContext('article-top');

    expect(BookPromoRotator::pool())->toContain(BookPromoRotator::currentSlug());
});

it('n\'affiche jamais deux fois le même livre sur une seule page', function () {
    BookPromoRotator::setContext('article-top');

    $premier = BookPromoRotator::resolveDistinct();
    $second = BookPromoRotator::resolveDistinct();

    expect($premier)->not->toBe($second);
});

it('parcourt les trois livres avant d\'en répéter un', function () {
    $servis = [
        BookPromoRotator::resolveDistinct(),
        BookPromoRotator::resolveDistinct(),
        BookPromoRotator::resolveDistinct(),
    ];

    expect(array_unique($servis))->toHaveCount(3);
    foreach ($servis as $slug) {
        expect(BookPromoRotator::pool())->toContain($slug);
    }
});

it('deux emplacements de rangs différents montrent deux livres différents', function () {
    // C'est le défaut constaté en production : « article-top » et « article-bottom »
    // affichaient le MÊME livre sur la même page. Le rang explicite le garantit.
    BookPromoRotator::setContext('article-top', 1);
    $haut = BookPromoRotator::currentSlug();

    BookPromoRotator::setContext('article-bottom', 2);
    $bas = BookPromoRotator::currentSlug();

    expect($haut)->not->toBe($bas);
});

it('reste silencieux et sans effet si le livre demandé n\'existe pas', function () {
    expect(BookPromoRotator::props('slug-qui-n-existe-pas'))->toBe([]);
});

it('ignore un livre non publié plutôt que de l\'afficher', function () {
    Book::updateOrCreate(['slug' => 'ia-pour-les-parents'], [
        'title' => 'Titre non publié',
        'subtitle' => 'Sous-titre',
        'slug' => 'ia-pour-les-parents',
        'genre' => 'Essai',
        'one_sentence_answer' => 'Une phrase.',
        'amazon_url_paperback' => 'https://amazon.ca/dp/TEST',
        'cover_image' => '/images/livres/ia-pour-les-parents-cover-600.jpg',
        'is_published' => false,
        'is_under_construction' => false,
    ]);

    expect(BookPromoRotator::props('ia-pour-les-parents'))->toBe([]);
});

it('fournit les quatre images réellement présentes sur disque et un lien d\'achat', function () {
    Book::updateOrCreate(['slug' => 'ia-pour-les-parents'], [
        'title' => "L'IA pour les parents",
        'subtitle' => 'Protéger tes enfants, encadrer les écrans',
        'slug' => 'ia-pour-les-parents',
        'genre' => 'Essai / parentalité numérique',
        'one_sentence_answer' => 'Un guide pratique pour les parents québécois.',
        'amazon_url_paperback' => 'https://amazon.ca/dp/B0H7CN9QG5',
        'cover_image' => '/images/livres/ia-pour-les-parents-cover-600.jpg',
        'date_published' => '2026-01-15',
        'is_published' => true,
        'is_under_construction' => false,
    ]);

    $props = BookPromoRotator::props('ia-pour-les-parents');

    expect($props['cover_url_webp'])->toBe('/images/livres/ia-pour-les-parents-cover-600.webp')
        ->and($props['cover_url_webp_2x'])->toBe('/images/livres/ia-pour-les-parents-cover-600.webp')
        ->and($props['cover_url_jpg'])->toBe('/images/livres/ia-pour-les-parents-cover-600.jpg')
        ->and($props['cover_url_300'])->toBe('/images/livres/ia-pour-les-parents-cover-300.webp')
        ->and($props['og_image'])->toBe('/images/livres/ia-pour-les-parents-og-1200x630.jpg')
        ->and($props['cta_url'])->toBe('https://amazon.ca/dp/B0H7CN9QG5')
        ->and($props['date_published'])->toBe('2026');
});

it('ne propose aucune variante d\'image en 1200 px, qui n\'existe pas sur disque', function () {
    // Garde-fou contre une régression précise : une première version du service
    // pointait le srcset 2x vers « -cover-1200.webp », un fichier inexistant. Le
    // navigateur aurait affiché une image cassée sur écran haute densité.
    Book::updateOrCreate(['slug' => 'ia-pour-les-parents'], [
        'title' => 'Livre de contrôle',
        'subtitle' => 'Sous-titre',
        'slug' => 'ia-pour-les-parents',
        'genre' => 'Essai',
        'one_sentence_answer' => 'Une phrase.',
        'amazon_url_paperback' => 'https://amazon.ca/dp/TEST',
        'cover_image' => '/images/livres/ia-pour-les-parents-cover-600.jpg',
        'is_published' => true,
        'is_under_construction' => false,
    ]);

    $props = BookPromoRotator::props('ia-pour-les-parents');

    foreach (['cover_url_webp', 'cover_url_webp_2x', 'cover_url_jpg', 'cover_url_300'] as $cle) {
        expect($props[$cle])->not->toContain('1200');
        expect(public_path(ltrim($props[$cle], '/')))->toBeFile();
    }
});

it('affiche le même livre toute la journée, pour coopérer avec les caches', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0, 'America/Toronto'));
    BookPromoRotator::setContext('article-top', 1);
    $matin = BookPromoRotator::currentSlug();

    Carbon::setTestNow(Carbon::create(2026, 8, 1, 21, 0, 0, 'America/Toronto'));
    BookPromoRotator::setContext('article-top', 1);
    $soir = BookPromoRotator::currentSlug();

    expect($matin)->toBe($soir);
});

it('change de livre le lendemain', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0, 'America/Toronto'));
    BookPromoRotator::setContext('article-top', 1);
    $aujourdhui = BookPromoRotator::currentSlug();

    Carbon::setTestNow(Carbon::create(2026, 8, 2, 9, 0, 0, 'America/Toronto'));
    BookPromoRotator::setContext('article-top', 1);
    $demain = BookPromoRotator::currentSlug();

    expect($aujourdhui)->not->toBe($demain);
});
