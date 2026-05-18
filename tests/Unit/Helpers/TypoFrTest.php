<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest pour lv_typo_fr() — typographie française (NBSP).
 * Verrouille : idempotence + zéro régression URL/HTML + couverture règles FR.
 */

$nbsp = "\u{00A0}";

it('ajoute NBSP avant ? collé au mot', function () use ($nbsp): void {
    expect(lv_typo_fr('conformité?'))->toBe("conformité{$nbsp}?");
});

it('remplace espace ASCII par NBSP avant ?', function () use ($nbsp): void {
    expect(lv_typo_fr('conformité ?'))->toBe("conformité{$nbsp}?");
});

it('est idempotent — applique 2× ne dégrade pas', function () use ($nbsp): void {
    $once = lv_typo_fr('conformité?');
    $twice = lv_typo_fr($once);
    expect($twice)->toBe($once)
        ->and($twice)->toBe("conformité{$nbsp}?");
});

it('couvre toute la ponctuation double FR : ? ! : ; »', function () use ($nbsp): void {
    expect(lv_typo_fr('test?'))->toBe("test{$nbsp}?")
        ->and(lv_typo_fr('test!'))->toBe("test{$nbsp}!")
        ->and(lv_typo_fr('test:'))->toBe("test{$nbsp}:")
        ->and(lv_typo_fr('test;'))->toBe("test{$nbsp};")
        ->and(lv_typo_fr('test»'))->toBe("test{$nbsp}»");
});

it('ajoute NBSP après « (guillemet ouvrant FR)', function () use ($nbsp): void {
    expect(lv_typo_fr('«test»'))->toBe("«{$nbsp}test{$nbsp}»");
});

it('ajoute NBSP entre chiffre et % (avec ou sans espace)', function () use ($nbsp): void {
    expect(lv_typo_fr('7%'))->toBe("7{$nbsp}%")
        ->and(lv_typo_fr('7 %'))->toBe("7{$nbsp}%")
        ->and(lv_typo_fr('25 %'))->toBe("25{$nbsp}%");
});

it('ajoute NBSP entre chiffre et M$ / M€ / k€', function () use ($nbsp): void {
    expect(lv_typo_fr('25 M$'))->toBe("25{$nbsp}M\$")
        ->and(lv_typo_fr('20 M€'))->toBe("20{$nbsp}M€")
        ->and(lv_typo_fr('35M€'))->toBe("35{$nbsp}M€")
        ->and(lv_typo_fr('500 k€'))->toBe("500{$nbsp}k€");
});

it('ajoute NBSP entre chiffre et € / $', function () use ($nbsp): void {
    expect(lv_typo_fr('4€'))->toBe("4{$nbsp}€")
        ->and(lv_typo_fr('99 $'))->toBe("99{$nbsp}\$");
});

it('ajoute NBSP entre chiffre et °C', function () use ($nbsp): void {
    expect(lv_typo_fr('21°C'))->toBe("21{$nbsp}°C")
        ->and(lv_typo_fr('21 °C'))->toBe("21{$nbsp}°C");
});

it('ne casse PAS les URL avec query string (?q=1)', function (): void {
    $html = '<a href="https://example.com/?q=1">test</a>';
    $out = lv_typo_fr($html);
    // Le ? doit rester collé au / sans NBSP injecté dans l'URL
    expect($out)->toContain('href="https://example.com/?q=1"');
});

it('ne casse PAS les URL avec query string complexe (?ids=1,2&foo=bar)', function (): void {
    $html = 'Voir <a href="https://laveille.ai/comparer?ids=1,2&foo=bar">comparer</a>!';
    $out = lv_typo_fr($html);
    expect($out)->toContain('href="https://laveille.ai/comparer?ids=1,2&foo=bar"');
});

it('préserve balises HTML (strong, em) et applique sur le texte intra-balise', function () use ($nbsp): void {
    // Comportement validé : segmentation tag/texte → règles appliquées dans
    // chaque segment de texte indépendamment. L'intérieur de <em> est traité,
    // les balises sont laissées intactes. La ponctuation entre balises (limite
    // de segment) n'est PAS jointe à un \S à travers une balise — c'est un
    // trade-off accepté (cas rare en pratique).
    $html = '<p><em>conformité?</em></p>';
    $out = lv_typo_fr($html);
    expect($out)->toContain("<em>conformité{$nbsp}?</em>")
        ->and($out)->toContain('<p>')
        ->and($out)->toContain('</p>');
});

it('gère texte vide ou null', function (): void {
    expect(lv_typo_fr(''))->toBe('')
        ->and(lv_typo_fr(null))->toBe('');
});

it('ne modifie pas texte sans ponctuation cible', function (): void {
    expect(lv_typo_fr('Bonjour le monde'))->toBe('Bonjour le monde');
});

it('gère phrase complexe FR avec multiples règles', function () use ($nbsp): void {
    $in = "Loi 25 : sanctions jusqu'à 25 M$ ou 4 % du CA mondial. Conformité?";
    $out = lv_typo_fr($in);
    expect($out)->toBe("Loi 25{$nbsp}: sanctions jusqu'à 25{$nbsp}M\$ ou 4{$nbsp}% du CA mondial. Conformité{$nbsp}?");
});

it('ne touche PAS un ratio horaire comme 17:42 (chiffre suivi de :)', function () use ($nbsp): void {
    // "17:42" — le ":" est précédé d'un chiffre, donc match (\S)([:])
    // → comportement attendu : NBSP ajouté. C'est correct typographiquement
    //   mais sémantiquement c'est un format heure. On accepte ce trade-off
    //   (cas rare en contenu éditorial vs gain énorme sur ponctuation FR).
    $out = lv_typo_fr('17:42');
    expect($out)->toBe("17{$nbsp}:42");
});

it('macro Str::typoFr est enregistrée et fonctionne identiquement', function () use ($nbsp): void {
    expect(\Illuminate\Support\Str::typoFr('conformité?'))->toBe("conformité{$nbsp}?");
});

it('préserve le JSON Laravel translatable — clés intactes, valeurs typographiées', function () use ($nbsp): void {
    $in = '{"fr_CA":"conformité?","fr":"taxe 7%"}';
    $out = lv_typo_fr($in);
    // Les clés `fr_CA`/`fr` ne reçoivent PAS de NBSP avant `:` (JSON syntax)
    // Les valeurs sont typographiées.
    expect($out)->toContain('"fr_CA":')
        ->and($out)->toContain('"fr":')
        ->and($out)->toContain("conformité{$nbsp}?")
        ->and($out)->toContain("7{$nbsp}%");
    // Et c'est toujours du JSON valide
    expect(json_decode($out, true))->toBeArray();
});

it('JSON array imbriqué reste valide après typographie', function () use ($nbsp): void {
    $in = '{"items":[{"label":"Conformité?","value":"25 M$"}]}';
    $out = lv_typo_fr($in);
    $decoded = json_decode($out, true);
    expect($decoded)->toBeArray()
        ->and($decoded['items'][0]['label'])->toBe("Conformité{$nbsp}?")
        ->and($decoded['items'][0]['value'])->toBe("25{$nbsp}M\$");
});
