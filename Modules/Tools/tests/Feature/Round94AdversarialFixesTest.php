<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

// Round 94 (2026-07-27) : passe adversariale fraîche après le lot round 93 (aria-labelledby modale
// aide + dédup id cartes personnalisées + échappement LIKE). 1 manque réel corrigé, sur 2 fonctions
// jumelles :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - copy() (bouton principal
// « Copier le prompt ») ET openIn() (boutons « Ouvrir dans » ChatGPT/Claude/Perplexity/Gemini)
// appelaient navigator.clipboard.writeText() dans un simple try/catch SYNCHRONE, qui n'intercepte
// jamais un rejet ASYNCHRONE de la Promise retournée. Le bouton passait à "Copié !" et un toast
// succès s'affichait INCONDITIONNELLEMENT, même quand la copie échouait réellement (permission
// refusée, contexte non sécurisé, politique navigateur) - faux positif sur l'action principale de
// l'outil. Fixé : les deux fonctions délèguent désormais à window.copyToClipboard() (helper déjà
// établi, Modules/FrontTheme/resources/views/layouts/master.blade.php:510-518), qui attend la
// Promise réelle et affiche succès/erreur en conséquence - copyText() (même fichier, ligne 638)
// suivait déjà ce pattern. openIn() garde window.open() SYNCHRONE (même pile d'appel que le clic,
// jamais dans un .then()) pour ne jamais risquer un blocage popup - seul le toast attend la
// résolution. Preuve comportementale complète (RED/GREEN prouvé pour les 2 fonctions, y compris le
// timing synchrone de window.open()) : tests/js/constructeur-prompts-clipboardpromise.test.cjs.
// Collision corrigée sur un test préexistant qui appelait navigator.clipboard.writeText()
// directement sans mocker window.copyToClipboard() : tests/js/constructeur-prompts-openin.test.cjs.

it('makes copy() delegate to window.copyToClipboard() instead of a fire-and-forget writeText() (round 94)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // #1593b (2026-08-07) : copy() copie désormais promptFilled (variables {{...}} substituées
    // quand remplies, voir get promptFilled()) plutôt que le prompt brut - même mécanisme
    // window.copyToClipboard()/.then(ok) inchangé, seul l'argument a changé de nom.
    expect($js)->toContain("window.copyToClipboard(this.promptFilled, i18n.promptCopied || 'Prompt copié').then(function(ok) {");
    expect($js)->toContain('if (!ok) return;');
});

it('makes openIn() delegate to window.copyToClipboard() while keeping window.open() synchronous (round 94)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // window.open() doit apparaître AVANT window.copyToClipboard() dans le code source (ordre
    // d'exécution synchrone préservé - jamais dans un .then() qui retarderait l'ouverture).
    $openPos = strpos($js, "window.open(url, '_blank', 'noopener');\n                window.copyToClipboard(payload, msg);");
    expect($openPos)->not->toBeFalse();
});
