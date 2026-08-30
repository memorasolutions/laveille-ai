<?php

declare(strict_types=1);

/*
 * Brique 2 - « Partir de mon brouillon » (SPEC-BRIQUE2). Rend RÉELLEMENT le blade du constructeur
 * (même pattern que ConstructeurGabaritsRenderTest.php, la leçon du 500 du 2026-08-20 : un point
 * d'entrée ajouté sans test de rendu direct de la vue peut casser silencieusement toute la page).
 * Réutilise le helper global ctRenderConstructeur(), déclaré dans tests/Pest.php (pas dans un
 * fichier de test - un fichier de test n'est pas garanti chargé avant celui-ci, cf. tests/Pest.php).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Database\Seeders\ToolSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('le blade du constructeur se rend SANS erreur et affiche le point d\'entrée « Partir de mon brouillon »', function () {
    (new ToolSeeder())->run();

    $html = ctRenderConstructeur();

    expect(strlen($html))->toBeGreaterThan(1000)
        ->and($html)->toContain('Partir de mon brouillon')
        ->and($html)->toContain('cpDraftText')
        ->and($html)->toContain('submitDraft()')
        // Lien vers l'Anonymiseur présent dans le HTML (même si masqué par x-show tant que
        // draftPiiWarning est faux) - la décision FERME 2026-08-04 (jamais de panneau de masquage
        // intégré) doit rester visible dans la vue rendue, pas seulement documentée en commentaire.
        ->and($html)->toContain('/outils/anonymiseur')
        // anonymizer-core.js chargé sur cette page (détection PII 100% client, voir §3 spec).
        ->and($html)->toContain('assets/tools/anonymiseur/anonymizer-core.js');
});
