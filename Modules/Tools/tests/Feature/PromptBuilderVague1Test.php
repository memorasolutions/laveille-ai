<?php

declare(strict_types=1);

/*
 * Garde-fous de rendu - vague 1 (2026-09-19, storage/app/constructeur-audit/brief-vague-1.md).
 * Défaut 3 (accessibilité) : couvre ce que seul le HTML SERVEUR peut verrouiller (les tests JS de
 * tests/js/constructeur-prompts-vague1-defauts.test.cjs couvrent la logique de calcul) - libellés
 * visibles for/id, jargon retiré, cibles tactiles 44px, focus programmatique des titres d'étape,
 * délégation @change/@input de l'étape 4.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('donne un libellé visible relié par for/id aux 4 champs "personnalisé" (rôle/verbe x2/audience)', function () {
    $html = ctRenderConstructeur();

    // Le placeholder n'est PAS un libellé (brief) : chaque champ personnalisé doit maintenant
    // avoir un <label for="..."> qui cible son id, en plus (ou à la place) du placeholder.
    foreach (['cpPersonaCustom', 'cpVerbCustom', 'cpVerbCustom2', 'cpAudienceCustom'] as $id) {
        expect($html)->toContain('for="' . $id . '"');
        expect($html)->toContain('id="' . $id . '"');
    }
});

it('a retiré le jargon "Prédéfini(e)" au profit de mots du public visé', function () {
    $html = ctRenderConstructeur();

    expect($html)->not->toContain('>Prédéfini<')->and($html)->not->toContain('>Prédéfinie<');
    // Remplacé par le même mot partout (persona, verbe x2, audience) - DRY, cohérence de l'outil.
    expect(substr_count($html, 'Dans une liste'))->toBe(4);
});

it('a retiré le jargon anglais non expliqué "zero-shot"/"few-shot" du texte affiché', function () {
    $html = ctRenderConstructeur();

    // Les NOMS de méthode affichés en petit gris sous le sélecteur ne portent plus l'anglais
    // technique non traduit - remplacés par les mêmes mots que le libellé principal du <select>.
    expect($html)->not->toContain('Méthode : zero-shot')
        ->and($html)->not->toContain('Méthode : few-shot')
        // La chaîne vit dans le blob JSON injecté (window.promptBuilderConfig.techniqueHints) -
        // les accents y sont échappés (é), donc on cherche le fragment SANS accent, présent
        // uniquement dans le nouveau libellé (jamais dans "Réponse directe (par défaut)" du menu).
        ->and($html)->toContain('directe) L');
});

it('le stepper porte trois états lisibles sans la couleur (aria-label systématique, pas seulement à l\'état complétée)', function () {
    $html = ctRenderConstructeur();

    // Avant : le nom accessible du bouton d'étape dépendait de .ct-stepper__label, mise en
    // display:none pour les étapes non actives en mobile (<520px) - un lecteur d'écran n'avait
    // alors AUCUN nom. Le bouton porte maintenant un aria-label toujours calculé (stepStateLabel).
    expect($html)->toContain(':aria-label="s[1] + \' - \' + stepStateLabel(s[0])"');
    expect($html)->toContain('stepState(s[0])');
    // L'ancien booléen stepComplete() ne pilote plus SEUL l'affichage du stepper (3 états).
    expect($html)->toContain("stepState(s[0]) === 'partiel'");
});

it('les titres des 4 étapes sont focusables par script (tabindex=-1 + id dédié)', function () {
    $html = ctRenderConstructeur();

    foreach ([1, 2, 3, 4] as $n) {
        expect($html)->toContain('id="cpStepHeading' . $n . '"');
    }
    expect(substr_count($html, 'class="ct-step-heading"'))->toBe(4);
});

it('délègue @change/@input sur le conteneur de l\'étape 4 pour armer step4Touched (jamais la navigation seule)', function () {
    $html = ctRenderConstructeur();

    expect($html)->toContain('@change="markStep4Touched()"');
    expect($html)->toContain('@input="markStep4Touched()"');
});

it('corrige le focus-trap de la modale "Tout recommencer ?" (bug aria-hidden confirmé par Chrome)', function () {
    $html = ctRenderConstructeur();

    expect($html)->toContain('id="cpResetTriggerBtn"');
    expect($html)->toContain("hide.bs.modal");
    expect($html)->toContain("hidden.bs.modal");
    expect($html)->toContain('document.activeElement.blur()');
});

it('porte les cibles tactiles à 44px minimum pour les boutons de pastille (chips) et les champs de l\'étape 4', function () {
    $html = ctRenderConstructeur();

    expect($html)->toContain('.ct-chip__x{display:inline-flex;align-items:center;justify-content:center;background:transparent;border:0;color:inherit;font-size:1rem;line-height:1;cursor:pointer;min-width:44px;min-height:44px;padding:0;}');
    expect($html)->toContain('.ct-chip__label{display:inline-flex;align-items:center;background:transparent;border:0;color:inherit;font:inherit;font-weight:500;cursor:pointer;padding:0;min-height:44px;}');
    expect($html)->toContain('#cpWizard select.form-control,#cpWizard select.form-control-sm,#cpWizard input.form-control-sm[type="text"]{min-height:44px;}');
});

it('n\'affiche plus le mensonge "complétée" sur une coche unique - la marque visuellement cachée liée à stepComplete() seule a disparu', function () {
    $html = ctRenderConstructeur();

    // L'ancien marquage isolé (span visually-hidden conditionné à stepComplete() seul, sans les
    // trois états) a été remplacé par l'aria-label systématique du bouton - vérifié ci-dessus.
    // Ici on verrouille juste l'absence de l'ancien couple exact (calcul en cascade).
    expect($html)->not->toContain("x-show=\"stepComplete(s[0])\">{{ __('complétée') }}");
});
