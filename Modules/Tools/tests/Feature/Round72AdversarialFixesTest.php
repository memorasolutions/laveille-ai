<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 72 (2026-07-27) : passe adversariale fraîche, périmètre élargi à
// Modules/Tools/resources/views/user/prompts/index.blade.php (page "Mes prompts", jamais
// auditée avant ce round - liée à constructeur-prompts via le lien "Nouveau prompt" et le flux
// de sauvegarde). Manque trouvé : 3 clés __() avec point final ('Aucun prompt sauvegardé.',
// '1 prompt sauvegardé.', ':count prompts sauvegardés.') absentes de lang/en.json, alors que la
// clé quasi-identique SANS point ('Aucun prompt sauvegardé', ligne 130 du même fichier) était
// déjà traduite. Ces 3 clés alimentent promptCountLabel() (x-text ligne 23) qui écrase le rendu
// serveur dès l'hydratation Alpine - donc à CHAQUE chargement de page, pas un flash transitoire.
// Un visiteur en locale EN voyait le compteur d'en-tête de « Mes prompts » rester en français.

it('has English translations for the prompt count label strings with trailing period (round 72)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Aucun prompt sauvegardé.',
        '1 prompt sauvegardé.',
        ':count prompts sauvegardés.',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('translates the prompt count label to English via app locale (round 72)', function () {
    app()->setLocale('en');

    expect(__('Aucun prompt sauvegardé.'))->toBe('No saved prompt.');
    expect(__('1 prompt sauvegardé.'))->toBe('1 saved prompt.');
    expect(__(':count prompts sauvegardés.', ['count' => 5]))->toBe('5 saved prompts.');
});
