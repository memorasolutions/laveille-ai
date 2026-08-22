<?php

declare(strict_types=1);

/**
 * Texte de partage OPTIMISÉ PAR RÉSEAU (spec 2026-08-20, panel « club des sages »).
 *
 * Avant ce lot, la barre de partage flottante (Modules/FrontTheme/resources/views/layouts/
 * master.blade.php) utilisait UN SEUL texte générique pour tous les réseaux, avec une
 * incohérence sur X : l'intent (`&text=`) portait un texte différent de celui copié dans le
 * presse-papiers par la modale « collez-le ». Ce fichier couvre :
 *   1. l'existence de 4 variantes calibrées (X/LinkedIn/Facebook/Messenger), distinctes du
 *      texte générique `share_text` et les unes des autres ;
 *   2. la fourchette de longueur approximative de chaque variante (grossièrement, pas une
 *      limite dure - la matière source varie en longueur) ;
 *   3. la cohérence texte-intent == texte-copié pour CHAQUE réseau (corrige l'incohérence X,
 *      étendue à Facebook/LinkedIn par la même mécanique) ;
 *   4. l'omission du paramètre Facebook `quote` quand la variante calibrée est vide ;
 *   5. la non-régression : la fiche rend toujours 200 avec la barre de partage présente.
 *
 * Extraction : les textes vivent à deux endroits dans le HTML rendu - encodés en JSON par
 * Illuminate\Support\Js::from() dans l'attribut Alpine `x-data` (délimiteur simple-guillemet),
 * et urlencodés dans les paramètres de requête des liens d'intent (Facebook/X/LinkedIn). Les
 * deux formes sont décodées puis comparées, jamais comparées à l'aveugle sur le texte brut.
 *
 * ADAPTÉ le 2026-08-21 (refonte des textes de partage PUBLICS, consultation à 5 modèles en 3
 * rounds) - deux changements de comportement qui rendaient certains tests d'origine faux :
 *   1. Les paramètres `&quote=` (Facebook) et `summary=` (LinkedIn) sont désormais RETIRÉS des
 *      liens d'intent, inconditionnellement (Meta ignore/interdit `quote` sur sharer.php,
 *      LinkedIn a déprécié `summary` sur cet endpoint) - les anciens tests 3 et 4 comparaient
 *      intent == presse-papiers pour ces deux réseaux ; ils sont remplacés par des tests qui
 *      vérifient l'ABSENCE du paramètre et la survie du texte via le presse-papiers.
 *   2. Un texte de partage est désormais TOUJOURS terminé (repli en cascade jusqu'au titre) -
 *      l'ancien test 5 attendait un texte Facebook VIDE en l'absence d'accroche ; ce n'est plus
 *      le comportement voulu (règle « texte TERMINÉ, jamais une amorce à compléter »), le test
 *      est remplacé par la garantie inverse.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixe spn = Share Per Network, distinct des autres fichiers) ─────────

function spnSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source partage par réseau',
        'url' => 'https://spn-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function spnArticle(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article de test partage par réseau '.$slug,
        'guid' => 'guid-spn-'.$slug,
        'url' => 'https://spn-source.exemple.com/'.$slug,
        'resolved_url' => 'https://spn-source.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * Décode la variante JS d'un réseau depuis l'attribut x-data (bloc `shareTexts: { ... }`,
 * délimiteur simple-guillemet posé par Illuminate\Support\Js::from()).
 */
function spnJsVariant(string $html, string $key): ?string
{
    if (! preg_match('/shareTexts:\s*\{([\s\S]*?)\},\s*openLi/', $html, $block)) {
        return null;
    }

    if (! preg_match('/'.preg_quote($key, '/')."\\s*:\\s*'((?:\\\\.|[^'\\\\])*)'/", $block[1], $m)) {
        return null;
    }

    return json_decode('"'.$m[1].'"');
}

// ── Point 1+2 : 4 variantes présentes, distinctes, calibrées grossièrement ─────────────────

it('renders 4 distinct share variants calibrated per network', function () {
    $source = spnSource();
    $article = spnArticle($source->id, 'variantes-riches', [
        'structured_summary' => [
            'hook' => 'Un nouvel outil transforme la façon dont les PME québécoises gèrent leur service à la clientèle au quotidien.',
            'why_important' => 'Il réduit les délais de réponse de plusieurs heures à quelques minutes.',
            'key_number' => '68 % des entreprises sondées rapportent une hausse mesurable de la satisfaction.',
            'action_concrete' => 'Évaluez un projet pilote de trois mois.',
        ],
        'category_tag' => 'Service client',
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    $fb = spnJsVariant($html, 'fb');
    $x = spnJsVariant($html, 'x');
    $li = spnJsVariant($html, 'li');
    $msg = spnJsVariant($html, 'msg');

    expect($fb)->not->toBeNull()
        ->and($x)->not->toBeNull()
        ->and($li)->not->toBeNull()
        ->and($msg)->not->toBeNull();

    // Les 4 variantes diffèrent entre elles - preuve qu'il ne s'agit pas du même texte générique
    // recopié 4 fois.
    expect(collect([$fb, $x, $li, $msg])->unique()->count())->toBe(4);

    // X : 0-1 hashtag (règle 2026-08-21, resserrée depuis 0-2), coeur de message dans une
    // fourchette large (matière source variable).
    $xFirstLine = explode("\n", $x)[0];
    expect(substr_count($x, '#'))->toBeLessThanOrEqual(1)
        ->and(mb_strlen($xFirstLine))->toBeGreaterThan(10)
        ->and(mb_strlen($xFirstLine))->toBeLessThanOrEqual(170);

    // LinkedIn : 250-600 caractères grossièrement, 1-3 hashtags (règle 2026-08-21, resserrée
    // depuis 2-4), premiers ~140-210 autonomes.
    expect(mb_strlen($li))->toBeGreaterThanOrEqual(200)
        ->and(mb_strlen($li))->toBeLessThanOrEqual(650)
        ->and(substr_count($li, '#'))->toBeGreaterThanOrEqual(1)
        ->and(substr_count($li, '#'))->toBeLessThanOrEqual(3)
        ->and(mb_strlen(explode("\n", $li)[0]))->toBeLessThanOrEqual(220);

    // Facebook : première ligne courte (0-140), ZÉRO hashtag (règle 2026-08-21, resserrée
    // depuis 0-1 : l'aperçu Open Graph fait déjà le travail visuel).
    $fbFirstLine = explode("\n", $fb)[0];
    expect(mb_strlen($fbFirstLine))->toBeLessThanOrEqual(145)
        ->and(substr_count($fb, '#'))->toBe(0);

    // Messenger : aucun hashtag, ton direct, contient le lien de l'article (son bouton n'ouvre
    // pas d'intent article - seul le texte copié porte le lien).
    expect(substr_count($msg, '#'))->toBe(0)
        ->and($msg)->toContain($article->slug);
});

// ── Point 3 : cohérence intent == presse-papiers, pour chaque réseau ────────────────────────

it('copies to the clipboard the exact same text used in the X share intent - fixes the incoherence', function () {
    $source = spnSource();
    $article = spnArticle($source->id, 'coherence-x', [
        'structured_summary' => ['hook' => 'Une avancée notable pour les PME.', 'key_number' => '37 % de gains mesurés.'],
        'category_tag' => 'Productivité',
    ]);

    $response = $this->get(route('news.show', $article));
    $html = $response->getContent();

    preg_match('/twitter\.com\/intent\/tweet\?url=[^"&]+&text=([^"]+)"/', $html, $m);
    expect($m[1] ?? null)->not->toBeNull();
    $hrefText = urldecode($m[1]);

    $jsText = spnJsVariant($html, 'x');

    expect($jsText)->not->toBeNull()->and($hrefText)->toBe($jsText);
});

it('never appends the deprecated quote parameter to the Facebook intent - text survives only via the clipboard (2026-08-21)', function () {
    $source = spnSource();
    $article = spnArticle($source->id, 'fb-sans-quote', [
        'structured_summary' => ['hook' => 'Une avancée notable pour les PME québécoises.'],
    ]);

    $response = $this->get(route('news.show', $article));
    $html = $response->getContent();

    // Meta ignore le paramètre `quote` sur sharer.php et interdit le préremplissage du texte de
    // partage - le lien ne le porte donc plus jamais, qu'une accroche soit disponible ou non.
    preg_match('/facebook\.com\/sharer\/sharer\.php\?u=[^"]+"/', $html, $m);
    expect($m[0] ?? '')->not->toBe('')
        ->and($m[0])->not->toContain('quote=');

    // Le texte continue de vivre par le presse-papiers (window.__openShare le copie avant
    // d'ouvrir l'intent) - non perdu par le retrait du paramètre.
    $jsText = spnJsVariant($html, 'fb');
    expect($jsText)->not->toBeNull()->and(trim((string) $jsText))->not->toBe('');
});

it('never appends the deprecated summary parameter to the LinkedIn intent - text survives only via the clipboard (2026-08-21)', function () {
    $source = spnSource();
    $article = spnArticle($source->id, 'li-sans-summary', [
        'structured_summary' => ['hook' => 'Une avancée notable pour les PME québécoises.', 'why_important' => 'Elle change les pratiques.'],
    ]);

    $response = $this->get(route('news.show', $article));
    $html = $response->getContent();

    // LinkedIn a déprécié le paramètre `summary` sur shareArticle - le lien ne le porte donc
    // plus jamais.
    preg_match('/linkedin\.com\/shareArticle\?mini=true&url=[^"]+"/', $html, $m);
    expect($m[0] ?? '')->not->toBe('')
        ->and($m[0])->not->toContain('summary=');

    $jsText = spnJsVariant($html, 'li');
    expect($jsText)->not->toBeNull()->and(trim((string) $jsText))->not->toBe('');
});

// ── Point 4 (adapté 2026-08-21) : un texte est TOUJOURS terminé, même sans accroche ────────

it('still copies a complete, non-empty Facebook text when no hook is available - a share text is never a blank amorce', function () {
    $source = spnSource();
    $article = spnArticle($source->id, 'fb-sans-accroche', [
        'title' => 'Un titre suffisant à lui seul comme dernier repli',
        'meta_description' => null,
        'structured_summary' => [],
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    // Ancien comportement (avant 2026-08-21) : texte VIDE si aucune accroche n'était calibrée.
    // Nouveau comportement voulu : un texte de partage est TOUJOURS terminé, jamais une amorce
    // vide - repli en cascade jusqu'au titre plutôt qu'un silence.
    $fb = spnJsVariant($html, 'fb');
    expect($fb)->not->toBeNull();
    expect(trim((string) $fb))->not->toBe('');

    // Le lien Facebook garde le paramètre u= (l'URL de l'article) mais plus jamais &quote=
    // (retiré inconditionnellement, cf. tests ci-dessus).
    preg_match('/facebook\.com\/sharer\/sharer\.php\?u=[^"]+"/', $html, $m);
    expect($m[0] ?? '')->not->toBe('')
        ->and($m[0])->not->toContain('quote=');
});

// ── Point 5 : non-régression ────────────────────────────────────────────────────────────────

it('still renders the fiche with the floating share bar present', function () {
    $source = spnSource();
    $article = spnArticle($source->id, 'non-regression', [
        'structured_summary' => ['hook' => 'Accroche de test.', 'key_points' => ['Point clé.']],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('class="share-float"', false)
        ->assertSee('share-btn share-x', false)
        ->assertSee('share-btn share-li', false)
        ->assertSee('share-btn share-fb', false)
        ->assertSee('share-btn share-msg', false);
});
