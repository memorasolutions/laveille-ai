<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 71 (2026-07-27) : passe adversariale fraîche, périmètre transitif complet tracé (tous les
// @include/<x-.../<script src récursivement, dans constructeur-prompts.blade.php ET dans chacun
// des fichiers déjà audités rounds 65-70). 2 manques réels trouvés :
// 1. Modules/Tools/app/Http/Controllers/SavedPromptController.php::duplicate() (route
//    POST /api/prompts/{id}/duplicate) n'avait AUCUNE couverture de test - ni happy-path, ni
//    guest-block, ni IDOR - contrairement à store/update/show/destroy/index. Le code était déjà
//    correctement scopé (public_id + user_id), donc pas exploitable en l'état, mais un endpoint
//    mutant vivant sans filet de régression. 3 tests ajoutés à SavedPromptControllerTest.php.
// 2. Modules/Core/resources/views/components/admin-copy-menu.blade.php : 3 chaînes FR en dur
//    ('Copié dans le presse-papiers', 'Actions admin (copier dans le presse-papier)', 'Copié ✓')
//    jamais passées par __(). Composant réellement rendu sur /outils/constructeur-prompts pour
//    les superadmins (share-btn.blade.php:85). Fixé via Illuminate\Support\Js::from(__(...)) -
//    PAS @json() ni {{ __(...) }} nu dans x-data, car x-data est un attribut HTML DOUBLE-QUOTÉ et
//    ces chaînes contiennent potentiellement des apostrophes ; Js::from() échappe en \uXXXX,
//    sûr à l'intérieur d'un attribut HTML (contrairement à json_encode brut).

it('has English translations for the admin-copy-menu strings (round 71)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Copié dans le presse-papiers',
        'Actions admin (copier dans le presse-papier)',
        'Copié ✓',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('reads admin-copy-menu strings from i18n instead of hardcoded French (round 71)', function () {
    $blade = file_get_contents(base_path('Modules/Core/resources/views/components/admin-copy-menu.blade.php'));

    expect($blade)->toContain("aria-label=\"{{ __('Actions admin (copier dans le presse-papier)') }}\"");
    expect($blade)->toContain('this.i18n.copiedToast');
    expect($blade)->toContain('i18n.copiedLabel');
    expect($blade)->not->toContain("window.toast('Copié dans le presse-papiers'");
    expect($blade)->not->toContain("? 'Copié ✓' :");
});
