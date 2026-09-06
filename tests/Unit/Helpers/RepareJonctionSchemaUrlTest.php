<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest pour lv_repare_jonction_schema_url() — ticket #2289 (2026-09-05). Un espace
 * insécable (U+00A0) glissé entre `https` et `://` casse le schéma d'une URL en markdown
 * (`Str::markdown()` produit `href="https ://domaine"`, résolu comme un chemin RELATIF).
 *
 * Verrouille DEUX choses opposées :
 *   1. la jonction schéma/séparateur EST réparée (les deux variantes vues en production :
 *      `https<invisible>://` et `https<invisible>:/` à un seul slash) ;
 *   2. la typographie française insécable AILLEURS dans le même texte N'EST JAMAIS touchée —
 *      c'est le contre-test qui distinguerait cette fonction d'un nettoyage général interdit.
 */

it('répare la jonction schéma/séparateur avec un espace insécable (double slash)', function (): void {
    $casse = "https\u{00A0}://exemple.com";
    expect(lv_repare_jonction_schema_url($casse))->toBe('https://exemple.com');
});

it('répare aussi la variante à un seul slash vue dans les journaux', function (): void {
    $casse = "https\u{00A0}:/exemple.com";
    expect(lv_repare_jonction_schema_url($casse))->toBe('https:/exemple.com');
});

it('répare http (sans s) de la même façon', function (): void {
    $casse = "http\u{00A0}://exemple.com";
    expect(lv_repare_jonction_schema_url($casse))->toBe('http://exemple.com');
});

it('répare plusieurs invisibles d\'affilée et plusieurs occurrences dans le même texte', function (): void {
    $casse = "Voir [le site](https\u{00A0}\u{200B}://a.com) puis [l'autre](https\u{00A0}://b.com).";
    expect(lv_repare_jonction_schema_url($casse))
        ->toBe("Voir [le site](https://a.com) puis [l'autre](https://b.com).");
});

it('est idempotent — appliqué 2× ne change plus rien après le premier passage', function (): void {
    $once = lv_repare_jonction_schema_url("https\u{00A0}://exemple.com");
    $twice = lv_repare_jonction_schema_url($once);
    expect($twice)->toBe($once)->toBe('https://exemple.com');
});

it('ne modifie pas une URL déjà propre', function (): void {
    $propre = 'https://exemple.com/page?x=1';
    expect(lv_repare_jonction_schema_url($propre))->toBe($propre);
});

it('gère texte vide ou null', function (): void {
    expect(lv_repare_jonction_schema_url(''))->toBe('')
        ->and(lv_repare_jonction_schema_url(null))->toBe('');
});

/**
 * CONTRE-ÉPREUVES : ces quatre tournures portent légitimement un espace insécable français
 * (avant %, $, une unité, ou un deux-points) et NE CONTIENNENT AUCUN "http"/"https" — un
 * correctif qui nettoierait trop large (trim ou classe \p{Z}\p{C} générale) les altérerait.
 * Ici la fonction ne doit STRICTEMENT RIEN changer : elle ne réagit qu'à la sous-chaîne
 * littérale http/https suivie d'invisibles puis de :/ ou ://.
 */
it('ne touche JAMAIS à la typographie française insécable qui n\'entoure pas un schéma', function (): void {
    $phrases = [
        "Le taux de succès atteint 8,5\u{00A0}% ce trimestre.",
        "L'abonnement coûte 24,99\u{00A0}\$ par mois.",
        "L'écran mesure 25\u{00A0}cm de diagonale.",
        "Voici\u{00A0}: le résultat de l'analyse.",
    ];

    foreach ($phrases as $phrase) {
        expect(lv_repare_jonction_schema_url($phrase))->toBe($phrase);
    }
});

it('contre-épreuve combinée : texte réel avec typographie FR ET une jonction cassée - seule la jonction bouge', function (): void {
    $texte = "Facturé 24,99\u{00A0}\$ par mois. Voici\u{00A0}: [le site officiel](https\u{00A0}://exemple.com) à consulter.";
    $attendu = "Facturé 24,99\u{00A0}\$ par mois. Voici\u{00A0}: [le site officiel](https://exemple.com) à consulter.";

    expect(lv_repare_jonction_schema_url($texte))->toBe($attendu);
});
