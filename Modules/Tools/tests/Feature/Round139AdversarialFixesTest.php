<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 139 (2026-07-30) : RÉGRESSION introduite par mon propre correctif du round 138.
//
// Le round 138 avait ajouté `activeField = null;` dans le handler du bouton qui déplie le panneau
// d'anonymisation, pour qu'une ouverture MANUELLE reparte sur le champ Tâche. Correct en soi.
//
// Mais openAnonWithTask() - le chemin déclenché par le bandeau « Masquer mes infos → » du garde-fou
// anti-PII - faisait exactement ceci :
//     activeField = field;        // mémorise le champ qui a déclenché l'alerte
//     toggleBtn.click();          // déplie le panneau
// Ce clic SYNTHÉTIQUE exécute le handler de façon SYNCHRONE, donc `activeField = null` s'exécutait
// immédiatement après l'affectation, avant même que l'utilisateur ait vu le panneau.
//
// Conséquence pour l'utilisateur : il tape une donnée personnelle dans « Exemples », suit le
// bandeau, anonymise, clique « Insérer » - et le texte anonymisé part dans la Tâche pendant que la
// donnée personnelle reste en place dans « Exemples ». Le garde-fou censé retirer la fuite se
// contentait de la recopier ailleurs.
//
// DEUX protections indépendantes, volontairement redondantes :
//   1. le handler ne remet la cible à zéro que sur un vrai geste utilisateur (round 146 : via un
//      paramètre explicite, plus via evt.isTrusted) ;
//   2. openAnonWithTask() mémorise la cible APRÈS avoir ouvert le panneau, donc l'ordre reste
//      correct même si ce paramètre venait à être mal passé.

const R139_PANEL_JS = 'public/assets/tools/constructeur-prompts/prompt-anon-panel.js';

it('only clears the target on a genuine user gesture (round 139)', function () {
    $js = file_get_contents(base_path(R139_PANEL_JS));

    // Ré-ancré au round 146. L'invariant protégé est IDENTIQUE : seule une ouverture MANUELLE
    // relâche la cible. Ce qui a changé, c'est la façon de le savoir - avant un signal implicite
    // (evt.isTrusted), désormais un paramètre explicite passé par l'appelant. Le test suit le
    // comportement, pas le mécanisme abandonné.
    expect($js)->toContain('function basculerPanneau(ouvertureManuelle) {');
    expect($js)->toContain('if (ouvertureManuelle) {');
    // Le clic humain déclare une ouverture manuelle...
    expect($js)->toContain('basculerPanneau(true);');
    // ...et l'ouverture programmatique déclare l'inverse.
    expect($js)->toContain('basculerPanneau(false);');
    // Le signal implicite ne doit pas revenir par la porte arrière.
    expect($js)->not->toContain('evt.isTrusted');
});

it('memorises the target AFTER opening the panel, not before (round 139)', function () {
    $js = file_get_contents(base_path(R139_PANEL_JS));

    // Ré-ancré au round 146 : l'ouverture programmatique n'est plus un clic simulé mais un appel
    // direct déclarant son intention. L'invariant d'ORDRE protégé ici est inchangé.
    $clickPos = strpos($js, 'basculerPanneau(false);');
    // Ré-ancré au round 141 : l'affectation passe désormais par setActiveField().
    // L'invariant protégé est IDENTIQUE (l'ordre), seul le nom de l'appel a changé.
    $assignPos = strpos($js, 'setActiveField(field);');

    expect($clickPos)->not->toBeFalse('Le clic programmatique d\'ouverture a disparu.');
    expect($assignPos)->not->toBeFalse('La mémorisation de la cible a disparu.');

    // C'est CET ordre qui constitue la seconde protection. S'il s'inverse, le clic synthétique
    // effacerait de nouveau la cible et le défaut du round 139 reviendrait.
    expect($assignPos)->toBeGreaterThan($clickPos);
});

it('still resets the target on manual open and after insertion (round 138 preserved)', function () {
    $js = file_get_contents(base_path(R139_PANEL_JS));

    // Ré-ancré au round 141. Le décompte littéral de « activeField = null; » ne s'applique plus :
    // les trois affectations sont passées par le point d'entrée unique setActiveField(), qui met
    // aussi le libellé du bouton à jour. Le comportement protégé (la cible EST relâchée à
    // l'ouverture manuelle et après insertion) est inchangé et vérifié à l'exécution par
    // tests/js/constructeur-prompts-insert-labelfor-scope.test.cjs.
    // Round 142 : SEUIL, pas égalité. Un 3e site de libération légitime est apparu (le cas « champ
    // démonté »). Compter à l'exact rendait ce test cassant à chaque ajout honnête, exactement le
    // travers qui avait déjà fait tomber ce fichier au round 141. L'invariant FORT reste celui de
    // la ligne au-dessus : une seule écriture de `activeField`, donc aucun contournement possible.
    expect(substr_count($js, 'setActiveField(null)'))->toBeGreaterThanOrEqual(2);
    expect(preg_match_all('/activeField\s*=\s*[^=]/', $js))->toBe(2);
});

it('renders the wizard after the round 139 fix (real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $this->get('/outils/constructeur-prompts')->assertOk();
});
