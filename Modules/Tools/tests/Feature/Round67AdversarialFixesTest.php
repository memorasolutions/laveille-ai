<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 67 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 manque réel -
// même classe i18n que le round 66, mais 2 clés supplémentaires (toast d'import de cartes
// personnalisées : limite de 10 atteinte / import partiel) encore absentes de lang/en.json,
// injectées via @json(__(...)) dans constructeur-prompts.blade.php (lignes 798-799) et
// consommées par constructeur-prompts-core.js (lignes 1059/1061). Comme __() renvoie la chaîne
// source (FR) telle quelle quand la clé JSON est absente, le fallback JS `i18n.xxx || 'FR...'`
// ne se déclenche jamais (la valeur n'est jamais falsy) : un utilisateur en locale EN voyait ces
// 2 toasts en français.

it('has English translations for the custom-cards import limit/partial toast strings (round 67)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($en)->toHaveKey('Limite de 10 cartes atteinte - aucune carte importée. Supprimez-en une puis réessayez.');
    expect($en)->toHaveKey('{imported} carte(s) importée(s) - {remaining} en attente (limite de 10 atteinte).');

    expect($en['Limite de 10 cartes atteinte - aucune carte importée. Supprimez-en une puis réessayez.'])
        ->not->toBe('Limite de 10 cartes atteinte - aucune carte importée. Supprimez-en une puis réessayez.');
    expect($en['{imported} carte(s) importée(s) - {remaining} en attente (limite de 10 atteinte).'])
        ->not->toBe('{imported} carte(s) importée(s) - {remaining} en attente (limite de 10 atteinte).');
});
