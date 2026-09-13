<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2531 (plan glossaire, étape 4) - preuve que les 12 pages de couverture du lot pilote
 * (Modules\Dictionary\Support\CoverageTerms) figurent au plan de site, et qu'aucune autre ne s'y
 * glisse : un terme publié hors liste ne doit jamais y apparaître, même s'il existe et est
 * publié - c'est la même garantie « pas de publication de masse » que côté route.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Modules\Dictionary\Support\CoverageTerms;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.noindex', false);
});

function scTerm(string $slug, bool $publie = true): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();

    return Term::create([
        'name' => [$locale => 'Terme sitemap '.$slug, 'fr' => 'Terme sitemap'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test pour le plan de site.', 'fr' => 'Définition de test.'],
        'is_published' => $publie,
    ]);
}

test('le plan de site contient les 12 URL de couverture du lot pilote', function () {
    foreach (CoverageTerms::SLUGS as $slug) {
        scTerm($slug);
    }

    $reponse = $this->get(route('sitemap'));

    $reponse->assertOk();
    foreach (CoverageTerms::SLUGS as $slug) {
        $reponse->assertSee(route('dictionary.coverage', $slug), false);
    }
});

test('le plan de site n\'inclut pas la page de couverture d\'un terme publié hors liste', function () {
    scTerm('agent-ia');
    $slugHorsListe = 'terme-sitemap-hors-liste-'.uniqid();
    $termHorsListe = scTerm($slugHorsListe);

    $reponse = $this->get(route('sitemap'));

    $reponse->assertOk();
    // La fiche du terme (glossaire ordinaire) reste bien présente...
    $reponse->assertSee($termHorsListe->getPublicUrl(), false);
    // ...mais jamais sa page de couverture (/actualites), qui n'existe pas pour ce terme.
    $reponse->assertDontSee($termHorsListe->getPublicUrl().'/actualites', false);
});

test('le plan de site n\'inclut pas la page de couverture d\'un terme du lot pilote non publié', function () {
    scTerm('anthropic', publie: false);

    $reponse = $this->get(route('sitemap'));

    $reponse->assertOk();
    $reponse->assertDontSee(route('dictionary.coverage', 'anthropic'), false);
});
