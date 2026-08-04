<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

// Round 90 (2026-07-27) : passe adversariale fraîche après le lot round 89 (3 astérisques
// #DC2626→#991B1B). 2 manques réels corrigés (couverts principalement côté JS - voir aussi
// tests/js/constructeur-prompts-hasLocalDataStale.test.cjs pour le bug hasLocalData) :
//
// 1. constructeur-prompts-core.js : init() (branche authentifiée) testait uniquement la présence
//    de la clé localStorage 'pb_history' (`if (localStorage.getItem('pb_history'))
//    self.hasLocalData = true;`), jamais son contenu réel. deletePrompt() (invité) écrit
//    littéralement la chaîne '[]' quand le dernier item local est supprimé - non-vide donc truthy
//    en JS - ce qui laissait la bannière + le bouton "Importer" affichés en permanence même après
//    connexion, sans jamais rien à importer. Fixé : parse + vérification de longueur du tableau,
//    + réinitialisation défensive dans importLocalStorage() sur son retour anticipé.
//
// 2. prompt-anon-panel.js : le bouton × de fermeture du bandeau d'avertissement PII proactif
//    (créé dynamiquement en JS, jamais dans le Blade/CSS statique) n'avait ni classe CSS ni
//    min-height/min-width - cible tactile ≈18-22px, en échec WCAG 2.2 AAA SC 2.5.5 (44×44).
//    Fixé : min-width:44px, min-height:44px, display:flex/align-items/justify-content:center
//    ajoutés en style inline (cohérent avec le pattern déjà utilisé sur anonBtn juste au-dessus).

it('checks pb_history content (not just presence) before setting hasLocalData (round 90)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->not->toContain("if (localStorage.getItem('pb_history')) self.hasLocalData = true;");
    expect($js)->toContain('if (Array.isArray(_lh) && _lh.length > 0) self.hasLocalData = true;');
    expect($js)->toContain('if (local.length === 0) { this.hasLocalData = false; return; }');
});

// Test du bouton dismissBtn (prompt-anon-panel.js) retiré le 2026-08-04 : le panneau
// d'anonymisation intégré au constructeur de prompts a été retiré (demande explicite de
// l'utilisateur, séparation des deux outils) - prompt-anon-panel.js n'existe plus.
