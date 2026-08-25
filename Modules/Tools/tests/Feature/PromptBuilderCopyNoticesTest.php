<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// La route publique de l'outil n'existe que si la fiche correspondante est active en base (sinon
// 404, pas 500) - même préparation que Round131AdversarialFixesTest, seul autre test qui rend
// réellement cette page.
beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);
});

// Signalement fondateur (2026-08-25) : « quand je fais copier le prompt, je me retrouve avec des
// variables dans mon prompt, normal ? ».
//
// Deux mécanismes distincts étaient confondus, et aucun n'était expliqué là où on les découvre,
// c'est-à-dire APRÈS avoir collé le texte dans l'IA :
//
//   1. Les repères ⟦DONNEES-...⟧ encadrent les données de l'utilisateur (contexte additionnel,
//      exemples) pour que le modèle ne les prenne jamais pour des consignes. Leur suffixe est tiré
//      au hasard à chaque copie (_generateDataDelimiter) précisément pour que personne ne puisse
//      imiter le repère de fermeture. L'aperçu écran, lui, affiche un suffixe FIXE pour rester
//      lisible : l'écart entre les deux passait pour un défaut, et le premier réflexe devant un
//      symbole incompris est de l'effacer - donc d'effacer la protection.
//   2. Une variable entre doubles accolades laissée vide est copiée telle quelle (promptFilled),
//      alors qu'un espace à remplir vide retombe sur son mot de départ. Seuls les espaces étaient
//      signalés avant la copie (unfilledSpacesMessage, orphanSpacesMessage).
//
// Ce test verrouille le RENDU, pas seulement la logique : la logique est couverte par
// tests/js/constructeur-prompts-variables.test.cjs, mais un texte peut être présent dans le JS et
// absent de la page. Il a d'ailleurs attrapé une régression réelle le jour même : des doubles
// accolades écrites dans un commentaire JavaScript à l'intérieur d'une balise script avaient été
// compilées par Blade en echo PHP, et la page rendait une 500.

it('rend la page du constructeur sans erreur pour un visiteur non connecté', function () {
    $this->get('/outils/constructeur-prompts')->assertOk();
});

it('explique les repères de données dans l\'aide, sans les décrire par un format périmé', function () {
    $reponse = $this->get('/outils/constructeur-prompts')->assertOk();
    $html = $reponse->getContent();

    expect($html)->toContain('Ces repères dans le prompt copié');
    expect($html)->toContain('DONNEES-');

    // L'aide décrivait les délimiteurs comme des « ### », format abandonné par le correctif de
    // sécurité du 2026-08-12 au profit du repère aléatoire. Un texte d'aide faux est pire que pas
    // d'aide : il apprend à chercher un symbole qui n'existe plus.
    expect($html)->not->toContain('Les délimiteurs (###)');
});

it('annonce avant la copie les variables laissées vides et les repères de données', function () {
    $html = $this->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Les deux mentions vivent dans le même bloc d'actions que celles des espaces à remplir,
    // avec le même contrat d'accessibilité (annonce polie, jamais bloquante).
    expect($html)->toContain('unfilledVariablesMessage');
    expect($html)->toContain('dataDelimitersMessage');
    expect($html)->toContain('unfilledVariablesCount > 0');
    expect($html)->toContain('isValid && hasDataDelimiters');

    // Les libellés doivent être injectés par le pont i18n, jamais laissés au seul repli JS.
    expect($html)->toContain('variableUnfilledOne');
    expect($html)->toContain('variableUnfilledMany');
    expect($html)->toContain('dataDelimitersNotice');
});

it('ne laisse aucune double accolade se compiler en echo PHP dans la page rendue', function () {
    $html = $this->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Symptôme exact de la régression du 2026-08-25 : Blade compile l'intérieur des balises script,
    // donc des doubles accolades dans un commentaire JavaScript deviennent un echo PHP. Quand
    // l'expression est « ... » (opérateur de décomposition), le rendu meurt en 500 avant d'arriver
    // ici ; quand elle est autre chose, elle fuit silencieusement dans la page. Les deux sont des
    // défauts, et cette assertion couvre le second.
    expect($html)->not->toContain('Object of class Closure');
    expect($html)->not->toContain('<?php');
});
