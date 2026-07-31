<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 70 (2026-07-27) : passe adversariale fraîche, périmètre élargi aux dépendances RENDUES
// hors des fichiers déjà audités (rounds 65-69) - même méthode que le round 69 (@include/<script src>
// réellement rendus sur constructeur-prompts.blade.php, pas seulement les 2 fichiers stricts).
// 4 manques réels trouvés :
// 1. Modules/Tools/resources/views/partials/share-btn.blade.php (inclus ligne 27) : aria-label/title/
//    toast jamais passés par __() - fixé (@json/__(), placeholder :name, accents corrigés Partagé/Copié).
// 2. Modules/Tools/resources/views/partials/fullscreen-btn.blade.php : aria-label statique jamais mis
//    à jour au toggle plein écran (seul .title l'était) - vrai bug WCAG 4.1.2 (aria-label prime sur
//    title dans le calcul du nom accessible). Fixé : aria-label mis à jour en même temps que title.
// 3. public/assets/tools/constructeur-prompts/prompt-anon-panel.js:117 : aria-label du bouton fermer
//    du bandeau PII codé en dur 'Fermer' au lieu de i18n.close (déjà défini pour cet usage exact).
// 4. Modules/Tools/resources/views/components/anonymizer-editor.blade.php (rendu via
//    <x-tools::anonymizer-editor> ligne 249) + Modules/FrontTheme/resources/views/partials/
//    tools-newsletter-cta.blade.php (inclus ligne 694) : 46 clés __() correctement encapsulées
//    mais absentes de lang/en.json - toutes ajoutées.

it('has English translations for the share-btn, anonymizer-editor and newsletter CTA strings (round 70)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Partager :name sur les réseaux sociaux',
        'Partager :name',
        'Partagé',
        'Copié dans le presse-papier',
        'Erreur de partage',
        "Détecter et anonymiser",
        'Tout anonymiser',
        'Votre texte',
        'Texte anonymisé',
        'Ma valeur',
        'Infolettre',
        'Cet outil vous a été utile ?',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('updates the fullscreen button aria-label (not just title) when toggling fullscreen (round 70)', function () {
    $js = file_get_contents(base_path('Modules/Tools/resources/views/partials/fullscreen-btn.blade.php'));

    // Les 2 endroits qui mettent à jour le libellé (entrée + sortie plein écran) doivent poser
    // aria-label, pas seulement title - sinon un lecteur d'écran reste bloqué sur l'ancien libellé
    // (aria-label prime sur title dans le calcul du nom accessible, WCAG 4.1.2).
    expect(substr_count($js, "setAttribute('aria-label'"))->toBe(2);
});

it('reads the anon-panel dismiss button aria-label from i18n.close instead of a hardcoded string (round 70)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    expect($js)->toContain("dismissBtn.setAttribute('aria-label', i18n.close || 'Fermer')");
});
