<?php

declare(strict_types=1);

/**
 * Module « signature éditoriale » (design doc SPEC-SIGNAL-HUMAIN, club des sages 93/100,
 * 2026-08-20) - la page /methodologie existait déjà en prod (audit du site, 2026-08-20) et n'a
 * pas été dupliquée : édition additive (nouvelle section « 9. Niveaux de preuve et vérification
 * des actualités »), voir Modules/FrontTheme/resources/views/methodologie.blade.php. Ce test
 * couvre l'ajout : route 200, présence des trois niveaux de preuve, page indexable.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('la page /methodologie répond 200', function () {
    $this->get(route('methodologie'))->assertOk();
});

it('la page /methodologie liste les trois niveaux de preuve (primaire/mixte/relais traduits)', function () {
    $response = $this->get(route('methodologie'));

    $response->assertOk()
        ->assertSee('Fondée sur la source originale', false)
        ->assertSee('Sources originale et média', false)
        ->assertSee('un média relais', false);
});

it('la page /methodologie décrit la vérification en deux couches et l\'attribution des sources', function () {
    $response = $this->get(route('methodologie'));

    $response->assertOk()
        ->assertSee('Composition avec preuve', false)
        ->assertSee('Relecture éditoriale humaine', false)
        ->assertSee('Vérifié par la rédaction de laveille.ai', false);
});

it('la page /methodologie n\'opte pas pour un noindex de niveau page (reste indexable)', function () {
    // Verif SOURCE robuste a l'environnement : le layout force un noindex global quand
    // config('app.noindex') est vrai (cas de l'env de test), ce qui n'a rien a voir avec
    // l'indexabilite propre de la page. Ce qui compte : la page ne DECLARE pas @section('page_noindex').
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/methodologie.blade.php'));

    expect($source)->not->toContain("@section('page_noindex'");
    $this->get(route('methodologie'))->assertOk();
});
