<?php

declare(strict_types=1);

/**
 * Textes de partage PUBLICS par réseau (visiteurs, barre de partage flottante) - couverture des
 * règles éditoriales issues d'une consultation à 5 modèles en 3 rounds (2026-08-21), portées par
 * Modules\Core\Concerns\HasAdminShareContents::publicShareTexts() (générique, partagé avec Blog/
 * Directory/Tools/Dictionary/Acronyms) et consommées par NewsArticle::publicShareTexts() /
 * Modules/News/resources/views/public/show.blade.php.
 *
 * Règles couvertes ici :
 *   - texte TERMINÉ, jamais une amorce à compléter (aucun « … » de troncature) ;
 *   - aucun libellé interne recopié (« Le chiffre à retenir », « Pourquoi ça compte ») ;
 *   - aucun appel à l'action creux (« Votre avis ? », « Qu'en pensez-vous ? ») ;
 *   - mots-clics : zéro Facebook/Messenger, 1 à 3 LinkedIn, 0 ou 1 X ;
 *   - le lien de la page vit dans les 4 textes ;
 *   - aucun texte ne commence par un émoji ;
 *   - Messenger tient en une seule phrase plus le lien.
 *
 * Distinct de Modules/News/tests/Feature/ShareTextPerNetworkTest.php (mécanique de rendu de la
 * barre de partage : cohérence intent/presse-papiers, paramètres d'URL) - ce fichier-ci couvre le
 * CONTENU éditorial des 4 textes. Helpers préfixés `pst` (Public Share Texts), indépendants de
 * ceux de ShareTextPerNetworkTest (préfixe `spn`) pour que ce fichier reste exécutable seul.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ───────────────────────────────────────────────────────────────────────────

function pstSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source textes de partage publics',
        'url' => 'https://pst-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function pstArticle(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article de test partage public '.$slug,
        'guid' => 'guid-pst-'.$slug,
        'url' => 'https://pst-source.exemple.com/'.$slug,
        'resolved_url' => 'https://pst-source.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * Décode la variante JS d'un réseau depuis l'attribut Alpine x-data (bloc `shareTexts: { ... }`,
 * délimiteur simple-guillemet posé par Illuminate\Support\Js::from() dans master.blade.php).
 */
function pstJsVariant(string $html, string $key): ?string
{
    if (! preg_match('/shareTexts:\s*\{([\s\S]*?)\},\s*openLi/', $html, $block)) {
        return null;
    }

    if (! preg_match('/'.preg_quote($key, '/')."\\s*:\\s*'((?:\\\\.|[^'\\\\])*)'/", $block[1], $m)) {
        return null;
    }

    return json_decode('"'.$m[1].'"');
}

/** Détecte un émoji en toute première position (large plage de blocs emoji courants). */
function pstStartsWithEmoji(string $text): bool
{
    return (bool) preg_match('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{2B00}-\x{2BFF}]/u', $text);
}

// ── Matière riche, avec libellés internes délibérément présents dans les champs bruts ─────────

it('produces four finished, rule-compliant public share texts and strips internal section labels', function () {
    $source = pstSource();
    $article = pstArticle($source->id, 'matiere-riche', [
        'structured_summary' => [
            // Libellés internes volontairement présents dans la matière brute : preuve que
            // stripSectionLabel() les retire réellement, pas seulement absence de cas de test.
            'hook' => 'Un outil québécois automatise désormais le tri des courriels pour les PME.',
            'why_important' => 'Pourquoi ça compte : les équipes de soutien gagnent plusieurs heures chaque semaine.',
            'key_number' => 'Le chiffre à retenir : 42 % des PME sondées ont réduit leur délai de réponse.',
            'action_concrete' => 'Essayez un projet pilote de deux semaines avant de généraliser.',
        ],
        'category_tag' => 'Productivité',
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $x = pstJsVariant($html, 'x');
    $li = pstJsVariant($html, 'li');
    $fb = pstJsVariant($html, 'fb');
    $msg = pstJsVariant($html, 'msg');

    expect($x)->not->toBeNull()
        ->and($li)->not->toBeNull()
        ->and($fb)->not->toBeNull()
        ->and($msg)->not->toBeNull();

    $all = ['x' => $x, 'linkedin' => $li, 'facebook' => $fb, 'messenger' => $msg];

    foreach ($all as $network => $text) {
        // Aucune troncature en plein mot (jamais de Str::limit + « … »).
        expect($text)->not->toContain('…');

        // Aucun libellé interne recopié.
        expect($text)->not->toContain('Le chiffre à retenir')
            ->and($text)->not->toContain('Pourquoi ça compte');

        // Aucun appel à l'action creux.
        expect($text)->not->toContain('Votre avis')
            ->and($text)->not->toContain("Qu'en pensez-vous");

        // Aucun texte ne commence par un émoji.
        expect(pstStartsWithEmoji($text))->toBeFalse("{$network} ne doit jamais commencer par un émoji");

        // Le lien de la page vit dans les 4 textes.
        expect($text)->toContain($article->slug)
            ->and($text)->toContain('http');
    }

    // Mots-clics par réseau : zéro Facebook/Messenger, 1 à 3 LinkedIn, 0 ou 1 X.
    expect(substr_count($fb, '#'))->toBe(0)
        ->and(substr_count($msg, '#'))->toBe(0)
        ->and(substr_count($li, '#'))->toBeGreaterThanOrEqual(1)
        ->and(substr_count($li, '#'))->toBeLessThanOrEqual(3)
        ->and(substr_count($x, '#'))->toBeLessThanOrEqual(1);

    // Messenger : une seule phrase (un seul saut de ligne, séparant le message du lien).
    expect(substr_count($msg, "\n"))->toBe(1);
});

// ── Matière minimale : le repli en cascade doit quand même produire un texte terminé ──────────

it('never truncates mid-sentence and always falls back to a complete, postable text with minimal source matter', function () {
    $source = pstSource();
    $article = pstArticle($source->id, 'matiere-minimale', [
        'title' => 'Un titre court sans ponctuation finale qui sert de seul repli disponible',
        'structured_summary' => [],
        'category_tag' => null,
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $x = pstJsVariant($html, 'x');
    $li = pstJsVariant($html, 'li');
    $fb = pstJsVariant($html, 'fb');
    $msg = pstJsVariant($html, 'msg');

    foreach (['x' => $x, 'linkedin' => $li, 'facebook' => $fb, 'messenger' => $msg] as $network => $text) {
        expect($text)->not->toBeNull("{$network} doit toujours exister");
        expect(trim((string) $text))->not->toBe('', "{$network} ne doit jamais être une amorce vide");
        expect($text)->not->toContain('…');
        expect($text)->toContain($article->slug);
    }

    // Sans catégorie, LinkedIn retombe sur son unique mot-clic générique - jamais zéro.
    expect(substr_count($li, '#'))->toBeGreaterThanOrEqual(1);

    // Messenger reste une seule phrase même en repli sur le titre.
    expect(substr_count($msg, "\n"))->toBe(1);
});
