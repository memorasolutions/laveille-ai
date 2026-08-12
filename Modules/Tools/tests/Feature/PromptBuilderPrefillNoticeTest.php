<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

// Régression 2026-08-12 : l'avis « ce prompt dépasse la limite de préremplissage » avait été placé
// À L'INTÉRIEUR d'un <template x-if>, à côté du <div> des boutons. Alpine ne clone QUE le premier
// enfant racine d'un x-if : le paragraphe n'a donc JAMAIS été inséré dans la page (code mort,
// prouvé en production sur laveille.ai avec un prompt de 5842 caractères, l'état réactif
// promptExceedsPrefillLimit valant pourtant true).
//
// Ces vérifications inspectent la SOURCE de la vue plutôt que le rendu : la faute est structurelle
// (position dans le gabarit), donc invisible à un test qui ne regarderait que le HTML produit
// pour un prompt court.

function promptBuilderViewSource(): string
{
    $path = base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php');
    expect(file_exists($path))->toBeTrue();

    return (string) file_get_contents($path);
}

it('places the prefill-threshold notice after BOTH open-in templates, never inside one', function () {
    $source = promptBuilderViewSource();

    $notice = strpos($source, 'x-show="promptExceedsPrefillLimit"');
    $firstBlock = strpos($source, '<template x-if="!openTargetHasPref">');
    $secondBlock = strpos($source, '<template x-if="openTargetHasPref">');

    expect($notice)->not->toBeFalse('l\'avis de seuil doit exister dans la vue');
    expect($firstBlock)->not->toBeFalse();
    expect($secondBlock)->not->toBeFalse();

    // Les deux gabarits x-if des boutons « Ouvrir dans » se suivent. L'avis doit venir APRÈS la
    // fermeture du second : à l'intérieur de l'un ou l'autre, Alpine ne le rendrait jamais (un
    // x-if ne clone que son premier enfant racine), et il ne vaudrait de toute façon que pour une
    // seule des deux dispositions alors que le seuil s'applique aux deux.
    $endOfSecondBlock = strpos($source, '</template>', $secondBlock);
    expect($endOfSecondBlock)->not->toBeFalse();
    expect($notice)->toBeGreaterThan(
        $endOfSecondBlock,
        'l\'avis de seuil doit être un frère des deux gabarits, jamais un enfant de l\'un d\'eux'
    );
});

it('keeps the notice bound to its own i18n string and to the reactive threshold getter', function () {
    $source = promptBuilderViewSource();

    expect($source)->toContain('x-text="openInLongPromptNotice"');
    expect($source)->toContain('openInLongPromptNotice:');
    expect(substr_count($source, 'x-show="promptExceedsPrefillLimit"'))
        ->toBe(1, 'un seul avis de seuil, valable pour les deux dispositions de boutons');
});
