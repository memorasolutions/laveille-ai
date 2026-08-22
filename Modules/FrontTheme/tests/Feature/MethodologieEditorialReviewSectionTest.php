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

// 2026-08-21 : la page est passée de DEUX à TROIS couches de vérification (v1.201.0), et la
// troisième a été renommée pour dire la vérité - « Relecture humaine, QUAND ELLE A EU LIEU »,
// puisqu'une fiche composée automatiquement n'est pas relue. Ce test suivait encore l'ancien
// libellé et échouait en silence depuis. Les libellés vérifiés ici sont volontairement des
// fragments SANS apostrophe : le rendu HTML les échapperait en &#039;.
it('la page /methodologie décrit la vérification en trois couches et l\'attribution des sources', function () {
    $response = $this->get(route('methodologie'));

    $response->assertOk()
        ->assertSee('Composition avec preuve', false)
        ->assertSee('Contre-vérification par plusieurs modèles', false)
        ->assertSee('Relecture humaine, quand elle a eu lieu', false)
        ->assertSee('Vérifié par la rédaction de laveille.ai', false);
});

// Module « vérification » (2026-08-21) : la page explique le mécanisme des verdicts et renvoie
// vers la liste réelle - une promesse de crédibilité doit être consultable.
it('la page /methodologie explique les verdicts de vérification et renvoie vers /verifications', function () {
    $response = $this->get(route('methodologie'));

    $response->assertOk()
        ->assertSee('contenu généré par une IA', false)
        ->assertSee('Consulter toutes les vérifications publiées', false)
        ->assertSee(route('news.verifications'), false);
});

it('la page /methodologie n\'opte pas pour un noindex de niveau page (reste indexable)', function () {
    // Verif SOURCE robuste a l'environnement : le layout force un noindex global quand
    // config('app.noindex') est vrai (cas de l'env de test), ce qui n'a rien a voir avec
    // l'indexabilite propre de la page. Ce qui compte : la page ne DECLARE pas @section('page_noindex').
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/methodologie.blade.php'));

    expect($source)->not->toContain("@section('page_noindex'");
    $this->get(route('methodologie'))->assertOk();
});
