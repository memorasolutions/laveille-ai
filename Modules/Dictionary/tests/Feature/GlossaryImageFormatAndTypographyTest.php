<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Verrou de non-régression (2026-09-19, correctifs images/typographie du glossaire) pour des
 * défauts qu'AUCUN test existant ne couvrait, et qui pouvaient donc être défaits sans que rien
 * ne rougisse :
 *
 *   (a) une fiche de glossaire AVEC image déclare og:image:height=669 - format réel des images
 *       du glossaire (1200x669), DISTINCT de celui des actualités (1200x630) ;
 *   (b) une page d'ACTUALITÉ déclare TOUJOURS og:image:height=630 - le point qui compte le
 *       plus : le layout modifié pour (a) (fronttheme::layouts.master) est partagé par plus de
 *       100 vues (actualités, blogue, annuaire, académie...), donc une régression y toucherait
 *       silencieusement tout le site, pas seulement le glossaire ;
 *   (c) le <picture> d'une fiche de glossaire ET les vignettes de la liste /glossaire (#2633)
 *       portent une <source> .webp ET un repli .jpg dont les URL sont RÉELLEMENT DIFFÉRENTES -
 *       hero_image étant TOUJOURS stocké en .webp (jamais l'original), l'ancien code renvoyait
 *       deux fois la MÊME URL webp. Un test qui vérifie seulement la présence d'un <img> aurait
 *       aussi passé avec ce défaut : ici on compare les deux URL entre elles ;
 *   (d) les libellés « Aussi appelé » et « Le saviez-vous » - y compris DANS LE TEXTE DE
 *       PARTAGE sérialisé en JSON par le widget flottant (fronttheme::layouts.master, section
 *       share_text) - ne portent jamais d'espace ORDINAIRE avant un deux-points. Trois
 *       constructions distinctes alimentaient ce même JSON (🔍 {kind} :, En termes simples :,
 *       Le saviez-vous :) et les trois avaient le défaut.
 *
 * Preuve de discrimination réelle (pas seulement « le test passe ») : (b), (c) et (c-bis) ont
 * chacun été vérifiés en réintroduisant manuellement le défaut d'origine dans le fichier source
 * (revert ponctuel, jamais committé) puis en relançant CE seul test - transcript RED puis GREEN
 * dans le rapport de livraison. Un test qui reste vert dans les deux états ne prouve rien.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Tests\Concerns\RegistersMysqlSqliteCompatFunctions;

uses(Tests\TestCase::class, RefreshDatabase::class);
uses(RegistersMysqlSqliteCompatFunctions::class);

// /glossaire (dictionary.index) trie via JSON_UNQUOTE(JSON_EXTRACT(...)), absent de sqlite -
// même polyfill que PublicDictionaryIndexPageTest.php dans ce même dossier, requis pour (c-bis).
beforeEach(fn () => $this->registerMysqlSqliteCompatFunctions());

// ── Helpers locaux (préfixés Gift - Glossary Image format & Typography - pour ne pas entrer en
//    conflit avec les autres fichiers de ce dossier) ─────────────────────────────────────────

/**
 * Crée une paire de fichiers .webp + .jpg RÉELS sur le disque public. Indispensable :
 * dictionary_hero_image_url()/_jpg_url()/_webp_url() appellent file_exists() directement sur
 * public_path(), aucune façade Storage mockable ici. Retourne le callback de nettoyage.
 */
function giftHeroFixture(string $slug): callable
{
    $webp = public_path("images/glossaire/{$slug}.webp");
    $jpg = public_path("images/glossaire/{$slug}.jpg");
    file_put_contents($webp, 'fixture-webp-'.$slug);
    file_put_contents($jpg, 'fixture-jpg-'.$slug);

    return function () use ($webp, $jpg) {
        @unlink($webp);
        @unlink($jpg);
    };
}

/** Construction directe (pas de TermFactory dans ce module - même convention que les autres
 *  fichiers de ce dossier, ex. ViewCounterDictionaryTest). Publié d'office : les routes ciblées
 *  exigent published(). */
function giftTerm(string $slug, array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();

    return Term::create(array_merge([
        'name' => [$locale => 'Terme gift '.$slug, 'fr' => 'Terme gift '.$slug],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'hero_image' => "images/glossaire/{$slug}.webp",
    ], $overrides));
}

function giftNewsArticle(string $slug): NewsArticle
{
    $source = NewsSource::firstOrCreate(
        ['url' => 'https://gift-source-test.exemple.com/rss'],
        ['name' => 'Source test gift', 'language' => 'fr', 'active' => true]
    );

    return NewsArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Titre actualité test gift',
        'guid' => 'guid-gift-'.$slug,
        'url' => 'https://exemple.com/gift-'.$slug,
        'description' => '',
        'summary' => 'Résumé test gift.',
        'slug' => $slug,
        'image_url' => 'https://exemple.com/gift-image.jpg',
        'pub_date' => now(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

// ── (a) glossaire = 669 ──────────────────────────────────────────────────────────────────────

it('(a) une fiche de glossaire avec image déclare og:image:height=669, format réel 1200x669', function () {
    $slug = 'gift-hauteur-glossaire-'.uniqid();
    $cleanup = giftHeroFixture($slug);

    try {
        giftTerm($slug);

        $reponse = $this->get('/glossaire/'.$slug);

        $reponse->assertOk();
        $reponse->assertSee('<meta property="og:image:width" content="1200">', false);
        $reponse->assertSee('<meta property="og:image:height" content="669">', false);
        $reponse->assertDontSee('<meta property="og:image:height" content="630">', false);
    } finally {
        $cleanup();
    }
});

// ── (b) actualité = 630, TOUJOURS - le layout modifié est partagé par >100 vues ────────────────

it('(b) une page d\'actualité déclare TOUJOURS og:image:height=630 (layout partagé par >100 vues)', function () {
    $slug = 'gift-hauteur-actualite-'.uniqid();
    giftNewsArticle($slug);

    $reponse = $this->get('/actualites/'.$slug);

    $reponse->assertOk();
    $reponse->assertSee('<meta property="og:image:height" content="630">', false);
    $reponse->assertDontSee('<meta property="og:image:height" content="669">', false);
});

// ── (c) <picture> de la fiche : source .webp ET repli .jpg RÉELLEMENT DIFFÉRENTS ───────────────

it('(c) le <picture> de la fiche porte une source .webp et un repli .jpg dont les URL diffèrent réellement', function () {
    $slug = 'gift-picture-fiche-'.uniqid();
    $cleanup = giftHeroFixture($slug);

    try {
        giftTerm($slug);

        $reponse = $this->get('/glossaire/'.$slug);
        $reponse->assertOk();

        $html = $reponse->getContent();
        preg_match('#<source srcset="([^"]+)" type="image/webp">#', $html, $mWebp);
        preg_match('#<img src="([^"]+)" alt="Terme gift[^"]*" loading="lazy">#', $html, $mImg);

        expect($mWebp[1] ?? null)->not->toBeNull()
            ->and($mImg[1] ?? null)->not->toBeNull()
            ->and($mWebp[1])->toEndWith('.webp')
            ->and($mImg[1])->toEndWith('.jpg')
            ->and($mImg[1])->not->toBe($mWebp[1]);
    } finally {
        $cleanup();
    }
});

// ── (c-bis, #2633) : même exigence sur les vignettes de /glossaire (JSON Alpine embarqué) ──────

it('(c-bis #2633) les vignettes de /glossaire portent heroImage(.jpg) et heroImageWebp(.webp) réellement distincts', function () {
    $slug = 'gift-vignette-liste-'.uniqid();
    $cleanup = giftHeroFixture($slug);

    try {
        giftTerm($slug);

        $reponse = $this->get('/glossaire');
        $reponse->assertOk();

        $html = $reponse->getContent();
        $posSlug = strpos($html, $slug);
        expect($posSlug)->not->toBeFalse();

        $posHero = strpos($html, 'heroImage', $posSlug);
        expect($posHero)->not->toBeFalse();
        $fenetre = substr($html, $posHero, 400);

        preg_match('/heroImage&quot;:&quot;([^&]+)&quot;/', $fenetre, $mImg);
        preg_match('/heroImageWebp&quot;:&quot;([^&]+)&quot;/', $fenetre, $mWebp);

        expect($mImg[1] ?? null)->not->toBeNull()
            ->and($mWebp[1] ?? null)->not->toBeNull()
            ->and($mImg[1])->toContain('.jpg')
            ->and($mWebp[1])->toContain('.webp')
            ->and($mImg[1])->not->toBe($mWebp[1]);
    } finally {
        $cleanup();
    }
});

// ── (d) OQLF : jamais d'espace ordinaire avant ':', y compris dans le JSON du widget de partage ─

it('(d) « Aussi appelé » et « Le saviez-vous » ne portent jamais une espace ordinaire avant le deux-points, y compris dans le JSON du widget de partage', function () {
    $slug = 'gift-oqlf-'.uniqid();

    giftTerm($slug, [
        'aliases' => ['Alias Gift Test'],
        'did_you_know' => 'Un fait de test pour la section Le saviez-vous.',
    ]);

    $reponse = $this->get('/glossaire/'.$slug);
    $reponse->assertOk();
    $html = $reponse->getContent();

    // Espace INSÉCABLE (U+00A0, écrite par point de code) exigée avant ':', jamais une espace
    // ordinaire (U+0020) ni l'entité &nbsp; (ce texte est aussi sérialisé en JSON, où l'entité
    // HTML s'afficherait telle quelle plutôt que d'être interprétée).
    expect($html)->toContain("Aussi appelé\u{00A0}:")
        ->and($html)->not->toContain('Aussi appelé :')
        ->and($html)->not->toContain('Aussi appelé&nbsp;:');

    // « Le saviez-vous? » sans espace (h2 visible) - et surtout plus AUCUNE occurrence de
    // « Le saviez-vous : » (espace ordinaire) nulle part sur la page, y compris dans le texte
    // de partage JSON (json_encode() n'échappe PAS une espace ASCII ordinaire : c'est
    // précisément ce qui rendait le défaut visible en clair dans le JSON rendu).
    expect($html)->toContain('Le saviez-vous?')
        ->and($html)->not->toContain('Le saviez-vous ?')
        ->and($html)->not->toContain('Le saviez-vous :');
});
