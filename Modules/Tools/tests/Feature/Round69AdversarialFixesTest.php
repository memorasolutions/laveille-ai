<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 69 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts. Le périmètre
// STRICT (constructeur-prompts.blade.php + constructeur-prompts-core.js) était enfin propre
// (225/225 chaînes vérifiées), donc l'agent a élargi le périmètre aux dépendances RENDUES sur
// la même page mais situées hors des 2 fichiers audités jusqu'ici - même classe i18n que les
// rounds 66/67/68, mais cette fois hors scope strict :
// 1. Modules/Tools/resources/views/partials/fullscreen-btn.blade.php (partagé 12x) : le titre du
//    bouton plein écran/quitter était un texte FR en dur dans le JS inline, jamais lu depuis __().
//    Fix : data-i18n-fullscreen/data-i18n-exit-fullscreen posés par Blade, lus par le JS.
// 2. public/assets/tools/constructeur-prompts/prompt-anon-panel.js : 6 chaînes (toasts + bandeau
//    d'avertissement PII) codées en dur en FR, jamais passées par __(). Fix : lecture depuis
//    window.promptBuilderConfig.i18n.* (posé par constructeur-prompts.blade.php via @json(__(...))),
//    avec le texte FR original en repli `||` (convention déjà établie dans core.js).
//
// PIÈGE DÉCOUVERT EN CORRIGEANT #2 : le compilateur Blade casse silencieusement
// @json(__('...(x, y, z)...')) dès que 2+ virgules apparaissent À L'INTÉRIEUR d'une parenthèse du
// texte (reproduit en isolation : "(a, b, c)" casse, "(a, b)" ne casse pas, ni "(a; b; c)"). La vue
// se compile alors en PHP tronqué (ex. json_encode(__('...') sans fermeture) → 500 sur TOUTE la
// page, pas juste une regression de test. Détecté uniquement parce que Round63AdversarialFixesTest
// (qui rend la page complète) a échoué en régression - preuve qu'un test qui RENDRT la vue est
// irremplaçable, un test qui vérifie seulement lang/en.json ne l'aurait jamais vu. Contournement :
// éviter 2+ virgules dans une parenthèse à l'intérieur d'un argument @json(__(...)) - utiliser des
// points-virgules ou reformuler.

it('has English translations for the fullscreen button strings (round 69)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    // Retrait du 2026-08-04 (demande explicite de l'utilisateur, séparation constructeur/
    // anonymiseur) : les 4 clés du bandeau anti-PII intégré (anonNeedTextFirst, anonImported,
    // anonInserted, anonPiiWarningField, anonMaskButton) ont été retirées avec le panneau
    // d'anonymisation intégré au constructeur de prompts - ce test ne vérifiait plus qu'un code
    // mort. Ne reste ici que ce qui est encore réellement rendu : le bouton plein écran.
    $keys = [
        'Plein écran',
        'Quitter le plein écran',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders the i18n-driven fullscreen button attributes (round 69)', function () {
    app()->setLocale('en');
    $html = (string) view('tools::partials.fullscreen-btn')->render();

    expect($html)->toContain('data-i18n-fullscreen="Fullscreen"');
    expect($html)->toContain('data-i18n-exit-fullscreen="Exit fullscreen"');
});
