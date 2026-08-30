<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest pour lv_strip_em_dash() — retrait du tiret cadratin (—, U+2014) de la prose
 * française composée par le site (CLAUDE.md règle 10 : « jamais de tiret cadratin »).
 * Verrouille : idempotence + substitution caractère pour caractère (espacement environnant
 * inchangé, qu'il soit présent ou non) + zéro régression sur un texte qui n'en contient pas.
 * Le test qui compte le plus n'est PAS ici : c'est NewsApplyCommandTest.php qui verrouille
 * que composed_summary.quote (citation verbatim) n'est JAMAIS passé à cette fonction.
 */

it('remplace un cadratin espacé par un trait d\'union, espacement environnant inchangé', function (): void {
    expect(lv_strip_em_dash('le modèle — variante repackagée'))
        ->toBe('le modèle - variante repackagée');
});

it('remplace un cadratin collé par un trait d\'union collé (aucun espace ajouté)', function (): void {
    expect(lv_strip_em_dash('CORE—FreeBSD'))->toBe('CORE-FreeBSD');
});

it('couvre les deux cadratins d\'une même phrase, chacun selon son espacement', function (): void {
    $in = 'le modèle CORE appliance—FreeBSD, OpenZFS—mais garde son sens.';
    expect(lv_strip_em_dash($in))->toBe('le modèle CORE appliance-FreeBSD, OpenZFS-mais garde son sens.')
        ->and(lv_strip_em_dash($in))->not->toContain('—');
});

it('est idempotent — appliqué 2× ne change plus rien après le premier passage', function (): void {
    $once = lv_strip_em_dash('avant — après');
    $twice = lv_strip_em_dash($once);
    expect($twice)->toBe($once)
        ->and($twice)->toBe('avant - après');
});

it('ne modifie pas un texte sans cadratin', function (): void {
    expect(lv_strip_em_dash('Bonjour le monde, tout va bien.'))
        ->toBe('Bonjour le monde, tout va bien.');
});

it('gère texte vide ou null', function (): void {
    expect(lv_strip_em_dash(''))->toBe('')
        ->and(lv_strip_em_dash(null))->toBe('');
});

it('ne touche pas au trait d\'union simple ni au tiret demi-cadratin', function (): void {
    expect(lv_strip_em_dash('porte-parole'))->toBe('porte-parole')
        ->and(lv_strip_em_dash('2024–2025'))->toBe('2024–2025');
});
