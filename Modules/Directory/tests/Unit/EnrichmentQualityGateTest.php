<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests unitaires de EnrichmentQualityGate (correctif incident SceneNote, 2026-08-14) - la
 * commande tools:reenrich-stale a écrit « aucune version officielle de cet outil ne dispose
 * d'un site web dédié » alors que l'adresse du produit figurait déjà dans la fiche. Aucun appel
 * réseau ici : la porte s'évalue entièrement sur des chaînes en mémoire.
 */

use Modules\Directory\Services\EnrichmentQualityGate;

uses(Tests\TestCase::class);

it('accepte une description propre, sans affirmation d\'absence, entités toutes ancrées', function () {
    $gate = new EnrichmentQualityGate();

    $description = "## À propos de SceneNote\nSceneNote est un outil de prise de notes pour scénaristes.\n\n"
        .'## Notre avis'."\n".'SceneNote se distingue par son intégration avec Final Draft.';
    $sourceText = 'SceneNote est un outil disponible sur https://scenenote.example. Intègre Final Draft.';

    $result = $gate->check($description, $sourceText, ['Nom : SceneNote']);

    expect($result['ok'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});

it('rejette la faute exacte constatée sur la fiche SceneNote : absence de site affirmée alors que l\'adresse est connue', function () {
    $gate = new EnrichmentQualityGate();

    $description = "## À propos de SceneNote\nAucune version officielle de cet outil ne dispose d'un site web dédié.";
    $knownFacts = ['Nom : SceneNote', 'Site officiel déjà connu (vérifié, ne jamais affirmer son inexistence) : https://scenenote.example'];

    $result = $gate->check($description, 'Résultat de recherche sans rapport.', $knownFacts);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toStartWith('absence_affirmee:');
});

it('rejette une description affirmant qu\'un outil n\'existe pas', function () {
    $gate = new EnrichmentQualityGate();

    $description = "## À propos de TestOutil\nCet outil n'existe pas sous ce nom sur le marché actuel.";

    $result = $gate->check($description, 'Texte source quelconque.', ['Nom : TestOutil']);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toStartWith('absence_affirmee:');
});

it('n\'échoue jamais à cause d\'une limitation de palier tarifaire légitime (pas une affirmation d\'absence du produit)', function () {
    $gate = new EnrichmentQualityGate();

    $description = "## Tarification\nLe plan gratuit ne dispose pas de l'accès API, réservé au plan Pro.";

    $result = $gate->check($description, 'Texte source quelconque.', ['Nom : TestOutil']);

    expect($result['ok'])->toBeTrue();
});

it('rejette une description qui invente une entité absente de la recherche et des données connues', function () {
    $gate = new EnrichmentQualityGate();

    $description = "## Notre avis\nSelon Global Ventures Capital, l'outil aurait levé des millions récemment.";
    $sourceText = 'Un outil de scénarisation pour les créateurs francophones.';

    $result = $gate->check($description, $sourceText, ['Nom : TestOutil']);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toStartWith('entite_absente:');
});

it('accepte une entité ancrée uniquement dans les données déjà connues de la fiche (pas dans la recherche)', function () {
    $gate = new EnrichmentQualityGate();

    $description = "## À propos de SceneNote\nSceneNote reste fidèle à sa mission d'origine.";
    $sourceText = 'Texte de recherche générique sans nom propre spécifique.';

    $result = $gate->check($description, $sourceText, ['Nom : SceneNote']);

    expect($result['ok'])->toBeTrue();
});

it('ignore les titres de section H2 dans le contrôle d\'ancrage des entités (gabarit de prompt fixe)', function () {
    $gate = new EnrichmentQualityGate();

    // "Notre Avis" pourrait être rendu par le modèle avec majuscule à "Avis" (variation de casse
    // d'un titre du gabarit) - ce n'est jamais un fait produit par le modèle, donc jamais évalué.
    $description = "## Notre Avis\nCet outil convient bien aux équipes en démarrage.";

    $result = $gate->check($description, 'Texte de recherche sans rapport.', ['Nom : TestOutil']);

    expect($result['ok'])->toBeTrue();
});

it('la porte peut être désactivée entièrement par configuration', function () {
    $gate = new EnrichmentQualityGate();
    config(['directory.reenrich_stale.quality_gate_enabled' => false]);

    $description = "Aucune version officielle de cet outil ne dispose d'un site web dédié.";

    $result = $gate->check($description, '', []);

    expect($result['ok'])->toBeTrue();
});

it('le contrôle d\'entités peut être désactivé isolément, sans désactiver le contrôle d\'absence', function () {
    $gate = new EnrichmentQualityGate();
    config(['directory.reenrich_stale.entity_check_enabled' => false]);

    $inventedEntityDescription = "## Notre avis\nSelon Global Ventures Capital, tout va bien.";
    $absenceDescription = "Aucune version officielle de cet outil ne dispose d'un site web dédié.";

    $withInventedEntity = $gate->check($inventedEntityDescription, 'Texte sans rapport.', ['Nom : TestOutil']);
    $withAbsenceClaim = $gate->check($absenceDescription, 'Texte sans rapport.', ['Nom : TestOutil']);

    expect($withInventedEntity['ok'])->toBeTrue()
        ->and($withAbsenceClaim['ok'])->toBeFalse();
});
