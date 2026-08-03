<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 68 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 3e round consécutif
// à trouver des clés i18n manquantes dans lang/en.json (même classe que les rounds 66/67). Cette
// fois : le toggle « audience prédéfinie / personnalisée » (réintroduit au round 60, v1.132.0)
// n'avait jamais été audité côté traduction. 3 clés manquaient - notamment les formes FÉMININES
// (« Prédéfinie »/« Personnalisée », accordées avec « audience ») distinctes des formes masculines
// déjà traduites ailleurs (« Prédéfini »/« Personnalisé », pour « format »).

it('has English translations for the audience preset/custom toggle strings (round 68)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($en)->toHaveKey("Mode de sélection de l'audience");
    expect($en)->toHaveKey('Prédéfinie');
    expect($en)->toHaveKey('Personnalisée');

    expect($en["Mode de sélection de l'audience"])->not->toBe("Mode de sélection de l'audience");
    expect($en['Prédéfinie'])->not->toBe('Prédéfinie');
    expect($en['Personnalisée'])->not->toBe('Personnalisée');
});
