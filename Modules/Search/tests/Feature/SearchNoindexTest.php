<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Audit AdSense 2026-08-20 (« contenu de faible valeur ») : /recherche?q=... passe noindex quand
 * total === 0, même mécanisme que les autres corrections de la spec (page_noindex, réutilisé tel
 * quel - cf. Modules/FrontTheme/resources/views/layouts/master.blade.php). PAS de délégation ici,
 * ce test n'est PAS exécuté par ce sous-agent (contrainte projet - le superviseur lance la suite
 * une seule fois, en série).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.noindex', false);
});

test('noindex sur /recherche quand aucun résultat', function () {
    $response = $this->get(route('search.index', ['q' => 'requetecompletementinventeexyzabc']));

    $response->assertOk();
    $response->assertSee('noindex, follow', false);
});

test('pas de noindex sur /recherche quand au moins un résultat', function () {
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'OutilRechercheTestUnique');
    $tool->setTranslation('slug', 'fr_CA', 'outil-recherche-test-unique');
    $tool->setTranslation('description', 'fr_CA', 'Description de test pour la recherche.');
    $tool->url = 'https://exemple-recherche.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();

    $response = $this->get(route('search.index', ['q' => 'OutilRechercheTestUnique']));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});
