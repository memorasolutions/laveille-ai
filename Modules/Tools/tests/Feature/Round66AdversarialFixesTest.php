<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 66 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 3e manque réel
// (les 2 premiers - localStorage non protégé dans deletePrompt()/clearHistory() - sont couverts
// côté comportemental par tests/js/constructeur-prompts-storageerrors2.test.cjs).
//
// Les 3 nouvelles chaînes ARIA/title ajoutées au round 65 pour l'accessibilité (« Chargement de
// votre historique en cours... », « Chargement de votre historique de prompts en cours... »,
// « Chargement du prompt en édition en cours... ») étaient absentes de lang/en.json - un visiteur
// en locale anglaise voyait ces annonces d'accessibilité s'afficher en français au milieu d'une
// interface autrement traduite (le correctif d'accessibilité du round 65 introduisait lui-même un
// trou i18n).

it('has English translations for the round 65 ARIA/title loading strings (round 66)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($en)->toHaveKey('Chargement de votre historique en cours...');
    expect($en)->toHaveKey('Chargement de votre historique de prompts en cours...');
    expect($en)->toHaveKey('Chargement du prompt en édition en cours...');

    expect($en['Chargement de votre historique en cours...'])->not->toBe('Chargement de votre historique en cours...');
    expect($en['Chargement de votre historique de prompts en cours...'])->not->toBe('Chargement de votre historique de prompts en cours...');
    expect($en['Chargement du prompt en édition en cours...'])->not->toBe('Chargement du prompt en édition en cours...');
});
