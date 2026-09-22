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

it('ne touche pas au ? déjà collé au mot (norme québécoise : aucune espace avant)', function (): void {
    expect(lv_typo_fr('conformité?'))->toBe('conformité?');
});

it('retire l\'espace ASCII avant ? au lieu de le remplacer par une insécable', function (): void {
    expect(lv_typo_fr('conformité ?'))->toBe('conformité?');
});

it('est idempotent — applique 2× ne dégrade pas', function (): void {
    $once = lv_typo_fr('conformité?');
    $twice = lv_typo_fr($once);
    expect($twice)->toBe($once)
        ->and($twice)->toBe('conformité?');
});

it('couvre toute la ponctuation FR : insécable avant : et », aucune espace avant ? ! ;', function () use ($nbsp): void {
    expect(lv_typo_fr('test?'))->toBe('test?')
        ->and(lv_typo_fr('test!'))->toBe('test!')
        ->and(lv_typo_fr('test:'))->toBe("test{$nbsp}:")
        ->and(lv_typo_fr('test;'))->toBe('test;')
        ->and(lv_typo_fr('test»'))->toBe("test{$nbsp}»");
});

// Section fusionnée depuis TypoOqlfTest.php (2026-09-21, supprimé pour ne pas dupliquer
// ce fichier) : verrouille la norme de l'Office québécois de la langue française, qui
// DIFFÈRE de l'usage français sur un point que le réflexe fait rater - au Québec,
// « ; », « ! » et « ? » ne prennent AUCUNE espace avant, alors que « : » et « » » en
// prennent une insécable. La règle unique qui existait jusqu'au 2026-09-21 ajoutait une
// insécable devant les cinq signes, donc fabriquait la faute au lieu de la corriger.
// Aucun contenu servi ne la portait, la commande `typo:apply-fr` n'étant pas planifiée -
// mais rien n'empêchait quelqu'un de la lancer. Ces tests sont ce qui empêche le retour
// en arrière. Les cas déjà couverts ci-dessus (ponctuation glued, sans espace existante)
// n'ont pas été redupliqués ; seuls les cas apportant une couverture distincte le sont
// (espace ordinaire à retirer, insécable déjà posée à nettoyer, ponctuation déjà conforme).

it('retire l\'espace ordinaire avant le point d\'exclamation (cas non collé)', function (): void {
    expect(lv_typo_fr('Vraiment !'))->toBe('Vraiment!');
});

it('retire l\'espace ordinaire avant le point-virgule (cas non collé)', function (): void {
    expect(lv_typo_fr('un ; deux'))->toBe('un; deux');
});

it('remplace l\'espace ordinaire avant le deux-points par une insécable', function () use ($nbsp): void {
    expect(lv_typo_fr('Voici : le résultat'))->toBe("Voici{$nbsp}: le résultat");
});

it('place une espace insécable après le guillemet ouvrant et avant le fermant, même si une espace ordinaire existait déjà', function () use ($nbsp): void {
    expect(lv_typo_fr('il dit « oui »'))->toBe("il dit «{$nbsp}oui{$nbsp}»");
});

it('conserve une ponctuation déjà conforme à la norme québécoise', function (): void {
    expect(lv_typo_fr('Prêt? Oui!'))->toBe('Prêt? Oui!');
});

it('retire aussi les insécables déjà posées avant interrogation et exclamation (nettoyage de l\'ancienne faute)', function (): void {
    expect(lv_typo_fr("prêt\u{00A0}?"))->toBe('prêt?');
    expect(lv_typo_fr("prêt\u{00A0}!"))->toBe('prêt!');
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

it('préserve balises HTML (strong, em) et applique sur le texte intra-balise', function (): void {
    // Comportement validé : segmentation tag/texte → règles appliquées dans
    // chaque segment de texte indépendamment. L'intérieur de <em> est traité,
    // les balises sont laissées intactes. La ponctuation entre balises (limite
    // de segment) n'est PAS jointe à un \S à travers une balise — c'est un
    // trade-off accepté (cas rare en pratique).
    $html = '<p><em>conformité?</em></p>';
    $out = lv_typo_fr($html);
    expect($out)->toContain('<em>conformité?</em>')
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
    expect($out)->toBe("Loi 25{$nbsp}: sanctions jusqu'à 25{$nbsp}M\$ ou 4{$nbsp}% du CA mondial. Conformité?");
});

it('ne touche PAS une heure numérique comme 17:42', function (): void {
    // 2026-09-21 : ce test verrouillait un COMPROMIS ASSUMÉ - « 17:42 » devenait « 17<NBSP>:42 »,
    // ce que le commentaire d'origine justifiait par « cas rare en contenu éditorial ». Le
    // compromis n'a plus lieu d'être : depuis le correctif des URL, le deux-points n'est traité
    // que s'il est SUIVI d'une espace ou de la fin du texte. Une heure reste donc intacte, ce qui
    // est d'ailleurs la norme de l'OQLF (heure numérique collée : 13:52:45).
    expect(lv_typo_fr('17:42'))->toBe('17:42');
    expect(lv_typo_fr('Il est 13:52:45 pile.'))->toBe('Il est 13:52:45 pile.');
});

it('ne touche PAS une adresse, un courriel ni un lien en TEXTE BRUT', function (string $texte): void {
    // Les cas HTML (<a href="...">) étaient déjà couverts plus haut. Ceux-ci ne l'étaient PAS,
    // et c'est exactement là que le défaut du 2026-09-21 mordait : la règle du deux-points
    // acceptait zéro espace, donc « https://x » devenait « https<NBSP>://x » dans de la prose,
    // dans du Markdown et dans les descriptions d'outils de l'annuaire. Sans ces cas NÉGATIFS,
    // rien n'aurait rougi - 54 lignes de l'annuaire allaient être abîmées par un rattrapage.
    expect(lv_typo_fr($texte))->toBe($texte);
})->with([
    'adresse https nue' => ['https://chat.openai.com'],
    'adresse http nue' => ['http://exemple.ca'],
    'adresse ftp' => ['ftp://serveur.ca/fichier'],
    'lien Markdown' => ['[Voir la fiche](https://laveille.ai/glossaire/mcp)'],
    'deux adresses sur la même ligne' => ['[https://x.ca](https://x.ca)'],
    'adresse courriel' => ['mailto:info@memora.ca'],
    'lien téléphonique' => ['tel:+15551234567'],
    'port dans une adresse' => ['http://127.0.0.1:8000/admin'],
    'adresse au fil de la prose' => ['Voir https://laveille.ai/actualites pour la suite.'],
]);

it('macro Str::typoFr est enregistrée et fonctionne identiquement', function (): void {
    expect(\Illuminate\Support\Str::typoFr('conformité?'))->toBe('conformité?');
});

it('préserve le JSON Laravel translatable — clés intactes, valeurs typographiées', function () use ($nbsp): void {
    $in = '{"fr_CA":"conformité?","fr":"taxe 7%"}';
    $out = lv_typo_fr($in);
    // Les clés `fr_CA`/`fr` ne reçoivent PAS de NBSP avant `:` (JSON syntax)
    // Les valeurs sont typographiées.
    expect($out)->toContain('"fr_CA":')
        ->and($out)->toContain('"fr":')
        ->and($out)->toContain('conformité?')
        ->and($out)->toContain("7{$nbsp}%");
    // Et c'est toujours du JSON valide
    expect(json_decode($out, true))->toBeArray();
});

it('JSON array imbriqué reste valide après typographie', function () use ($nbsp): void {
    $in = '{"items":[{"label":"Conformité?","value":"25 M$"}]}';
    $out = lv_typo_fr($in);
    $decoded = json_decode($out, true);
    expect($decoded)->toBeArray()
        ->and($decoded['items'][0]['label'])->toBe('Conformité?')
        ->and($decoded['items'][0]['value'])->toBe("25{$nbsp}M\$");
});

// Régression : entités HTML (&rsquo; &#039; &#x27; &amp; &nbsp;...) cassées
// par la règle NBSP-avant-`;` — le `;` de fin d'entité matchait la règle
// "ponctuation double FR" et se faisait précéder d'un NBSP, cassant l'entité
// (elle ne se décode plus, ex. `&rsquo ;` s'affiche en clair au lieu de `'`).
it('ne casse PAS une entité HTML &rsquo; (apostrophe typographique)', function (): void {
    $in = 'Votre détecteur d&rsquo;IA vous ment-il ?';
    $out = lv_typo_fr($in);
    expect($out)->toContain('&rsquo;')
        ->and($out)->not->toContain('&rsquo ;')
        ->and($out)->not->toContain("&rsquo\u{00A0};");
});

it('ne casse PAS les entités numériques &#039; et &#x27;', function (): void {
    expect(lv_typo_fr('Test d&#039;entité numérique?'))->toContain('&#039;')
        ->and(lv_typo_fr('Test d&#x27;entité hex?'))->toContain('&#x27;');
});

it('ne casse PAS &amp; &nbsp; &eacute; (entités courantes diverses)', function (): void {
    expect(lv_typo_fr('Table &amp; chaise?'))->toContain('&amp;')
        ->and(lv_typo_fr('Mot1&nbsp;mot2!'))->toContain('&nbsp;')
        ->and(lv_typo_fr('Caf&eacute; chaud?'))->toContain('&eacute;');
});

it('retire quand même l\'espace avant la ponctuation qui suit une entité protégée', function (): void {
    // Le segment texte qui suit l'entité ("IA vous ment-il ?") est traité
    // indépendamment : la règle 1b y retire l'espace avant "?" normalement.
    $out = lv_typo_fr('Votre détecteur d&rsquo;IA vous ment-il ?');
    expect($out)->toContain('il?')
        ->and($out)->toContain('&rsquo;');
});

it('reste idempotent sur un texte mêlant entité HTML et ponctuation FR normale', function (): void {
    $in = "Votre détecteur d&rsquo;IA vous ment-il ? Loi 25 : 4 % du CA.";
    $once = lv_typo_fr($in);
    $twice = lv_typo_fr($once);
    expect($twice)->toBe($once)
        ->and($once)->toContain('&rsquo;');
});
