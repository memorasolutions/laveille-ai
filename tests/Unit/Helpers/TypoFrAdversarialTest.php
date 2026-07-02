<?php

declare(strict_types=1);

/**
 * TEST ADVERSARIAL TEMPORAIRE - Audit sécurité 2026-07-02
 * Vérifie l'absence de régression XSS/casse sur lv_typo_fr() avec des chaînes
 * mêlant balise ET entité imbriquées de façon pathologique.
 */

$nbsp = "\u{00A0}";

it('ne casse pas une entité imbriquée DANS un attribut de balise (<a href="&amp;">)', function (): void {
    $html = '<a href="&amp;">test</a>?';
    $out  = lv_typo_fr($html);

    // La balise entière (avec son attribut contenant l'entité) doit rester
    // BYTE-FOR-BYTE identique : le split capture la balise `<a href="&amp;">`
    // comme UN SEUL token (regex `<[^>]*>` ne s'arrête pas à `&`), donc
    // l'entité à l'intérieur de l'attribut n'est jamais vue comme "texte".
    expect($out)->toContain('<a href="&amp;">')
        ->and($out)->not->toContain('&amp ;') // pas de NBSP injecté avant le ';' de l'entité
        ->and($out)->not->toContain('&amp' . "\u{00A0}" . ';');
});

it('ne casse pas plusieurs entités imbriquées dans un attribut avec ponctuation FR adjacente', function () use ($nbsp): void {
    $html = '<a href="https://x.test/?a=1&amp;b=2" title="Test &amp; Test">lien</a> !';
    $out  = lv_typo_fr($html);

    expect($out)->toContain('href="https://x.test/?a=1&amp;b=2"')
        ->and($out)->toContain('title="Test &amp; Test"')
        // Trade-off DÉJÀ documenté (TypoFrTest.php "préserve balises HTML") : la
        // ponctuation séparée de la balise fermante par un espace n'est PAS jointe
        // à travers la frontière de segment (pas un \S collé). Comportement inchangé
        // par la segmentation à 3 voies, pas une régression de sécurité.
        ->and($out)->toContain('</a> !');
});

it('entité juste avant une balise ouvrante ne fusionne pas ponctuation à travers la frontière', function (): void {
    // Entité suivie immédiatement d'une balise : deux tokens protégés consécutifs.
    $html = 'Prix&nbsp;<strong>25%</strong>';
    $out  = lv_typo_fr($html);

    expect($out)->toContain('&nbsp;')
        ->and($out)->toContain('<strong>')
        ->and($out)->toContain('25' . "\u{00A0}" . '%'); // texte intra-balise typographié normalement
});

it('balise avec entité ET attribut contenant un point-virgule littéral suivi de texte reste intacte', function (): void {
    // Cas pathologique : attribut contenant un `;` qui n'est PAS la fin d'une entité
    // (ex. style inline avec plusieurs déclarations). La balise entière est un seul
    // token capturé par `<[^>]*>` donc le `;` interne n'est jamais exposé au moteur
    // de règles NBSP (qui ne s'applique qu'aux segments "texte pur").
    $html = '<span style="color:red;font-weight:bold">Texte&amp;suite</span>?';
    $out  = lv_typo_fr($html);

    expect($out)->toContain('<span style="color:red;font-weight:bold">')
        ->and($out)->toContain('&amp;')
        ->and($out)->not->toContain('bold&nbsp;"') // pas d'altération de l'attribut
        // Aucun espace entre </span> et "?" dans l'input : pas de \S à joindre à
        // travers la frontière de segment, donc sortie strictement identique à l'input
        // sur ce point (comportement neutre, pas un bug).
        ->and($out)->toContain('</span>?');
});

it('entité malformée (sans point-virgule) est traitée comme texte pur, pas comme entité protégée', function (): void {
    // `&amp` sans `;` ne matche pas la regex d'entité (`&[a-zA-Z]+;`) donc retombe
    // en texte pur : le `&` littéral doit survivre sans injection de NBSP parasite
    // qui casserait davantage une entité déjà invalide.
    $html = 'Table &amp suite?';
    $out  = lv_typo_fr($html);

    expect($out)->toContain('&amp')
        ->and($out)->toContain('suite' . "\u{00A0}" . '?');
});

it('idempotence sur le cas pathologique balise+entité imbriquées (double application ne dégrade pas)', function (): void {
    $html  = '<a href="&amp;">test</a> : <em>fin&nbsp;!</em>';
    $once  = lv_typo_fr($html);
    $twice = lv_typo_fr($once);

    expect($twice)->toBe($once);
});
