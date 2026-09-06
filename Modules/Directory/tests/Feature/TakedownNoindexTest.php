<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * #2288 - MESURE du 2026-09-06 : la fiche d'outil (public/show.blade.php ligne 431) pose un lien
 * route('directory.takedown.create', $tool->resolveTranslatedSlug()) sur CHACUNE des 2202 fiches
 * de l'annuaire. La route /annuaire/retrait/{slug?} sert le MEME formulaire quel que soit le slug :
 * cela fabrique 2202 adresses distinctes au contenu identique, dont Google en avait deja explore 91.
 *
 * Le correctif est @section('page_noindex', true) sur la vue du FORMULAIRE seulement.
 *
 * CONTRE-EPREUVE DE CONSERVATION (obligatoire, motif mesure du 2026-09-04) : une assertion
 * d'absence ne prouve jamais qu'on n'a pas trop coupe. La page de POLITIQUE de retrait est unique,
 * utile, et vaut comme signal de conformite : elle DOIT rester indexable. Le second test le prouve.
 *
 * Chaine assertee : la valeur EXACTE rendue par FrontTheme master.blade.php ligne 14, jamais un
 * simple str_contains('noindex') qui passerait aussi sur un commentaire ou un script tiers.
 */

// Pest.php n'etend Tests\TestCase que sur « Feature » et Modules/Academy : chaque test de
// Modules/Directory declare son uses() lui-meme (mesure du 2026-08-23, memoire projet).
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('le formulaire de demande de retrait porte le noindex (2202 adresses, meme contenu)', function () {
    // MESURE du 2026-09-06 : en environnement de test APP_NOINDEX est VRAI, et master.blade.php
    // ligne 11 rend alors « noindex, nofollow » pour TOUTE page - ce qui rendrait ce test vert
    // sans le correctif. On neutralise cette branche pour mesurer reellement @section('page_noindex').
    config(['app.noindex' => false]);
    $this->get('/annuaire/retrait')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow, max-image-preview:large">', false);
});

test('la POLITIQUE de retrait reste indexable - contre-epreuve de conservation', function () {
    config(['app.noindex' => false]);
    $this->get('/annuaire/politique-retrait')
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow', false)
        ->assertDontSee('<meta name="robots" content="noindex', false);
});
