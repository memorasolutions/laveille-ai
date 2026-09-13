<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve HTTP de bout en bout de la section publique « Dans l'actualité » (ticket #2524,
 * étape 2 - la partie visible du plan glossaire). Cette section liste, sur la fiche publique
 * d'un terme, jusqu'à 5 actualités les plus récentes dont la liaison news_article_term est
 * APPROUVÉE (colonne is_approved, doctrine « désapprouver, jamais supprimer ») et dont
 * l'article est PUBLIÉ. Voir Modules\Dictionary\Models\Term::approvedNewsArticles() et
 * Modules/Dictionary/resources/views/public/show.blade.php.
 *
 * Convention du module (même helpers que ViewCounterDictionaryTest et
 * PublicDictionaryIndexPageTest) : pas de TermFactory ni de NewsArticleFactory, construction
 * directe des modèles.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Da pour éviter tout conflit inter-fichiers) ────────────────────

function daSource(): NewsSource
{
    // firstOrCreate : l'URL est unique en base, plusieurs tests de ce fichier partagent la source.
    return NewsSource::firstOrCreate(
        ['url' => 'https://da-source-test.exemple.com/rss'],
        ['name' => 'Source dans l\'actualité test', 'language' => 'fr', 'active' => true]
    );
}

function daTerm(array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-dans-actu-'.uniqid();

    return Term::create(array_merge([
        'name' => [$locale => 'Terme dans actu '.uniqid(), 'fr' => 'Terme dans actu'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test pour la section dans l\'actualité.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ], $overrides));
}

function daArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = daSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Titre brut actualite dans actu {$i}",
        'guid' => "guid-da-{$suffix}",
        'url' => "https://exemple.com/da-{$suffix}",
        'description' => '',
        'summary' => "Résumé actualité dans actu {$i}",
        'slug' => "da-test-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/** Attache un article au terme via le pivot news_article_term, source 'auto' par défaut. */
function daLier(Term $term, NewsArticle $article, bool $approuve = true): void
{
    $article->terms()->attach($term->id, ['source' => 'auto', 'is_approved' => $approuve]);
}

// ── Cas 1 : article publié ET approuvé → section affichée, titre + lien ────────────────────

it('affiche la section « Dans l\'actualité » avec le titre optimisé et un lien vers la fiche de l\'article', function () {
    $term = daTerm();
    $article = daArticle([
        'title' => 'Titre brut jamais affiche pour cet article',
        'seo_title' => 'Titre optimise de larticle dans actu',
    ]);
    daLier($term, $article, true);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('Dans l\'actualité');
    // Repli d'affichage « titre optimisé sinon titre brut » : le seo_title prime sur title.
    $reponse->assertSee('Titre optimise de larticle dans actu');
    $reponse->assertDontSee('Titre brut jamais affiche pour cet article');
    $reponse->assertSee(route('news.show', $article->slug), false);
});

// ── Témoin négatif : un terme sans aucune liaison n'émet AUCUN balisage de section ──────────

it('n\'émet AUCUN balisage de section pour un terme sans aucune actualité liée (témoin négatif)', function () {
    $term = daTerm();

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertDontSee('Dans l\'actualité');
});

// ── Cas 2 : liaison désapprouvée → n'apparaît pas ───────────────────────────────────────────

it('n\'affiche pas une actualité dont la liaison a été désapprouvée par un administrateur', function () {
    $term = daTerm();
    $approuve = daArticle(['title' => 'Actualite approuvee visible']);
    $desapprouve = daArticle(['title' => 'Actualite desapprouvee invisible']);
    daLier($term, $approuve, true);
    daLier($term, $desapprouve, false);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('Actualite approuvee visible');
    $reponse->assertDontSee('Actualite desapprouvee invisible');
});

// ── Cas 3 : article non publié → n'apparaît pas ─────────────────────────────────────────────

it('n\'affiche pas une actualité approuvée mais non publiée', function () {
    $term = daTerm();
    $publie = daArticle(['title' => 'Actualite publiee visible', 'is_published' => true]);
    $brouillon = daArticle(['title' => 'Actualite brouillon invisible', 'is_published' => false]);
    daLier($term, $publie, true);
    daLier($term, $brouillon, true);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('Actualite publiee visible');
    $reponse->assertDontSee('Actualite brouillon invisible');
});

// ── Cas 4 : plafond de 5 respecté ────────────────────────────────────────────────────────────

it('affiche au plus 5 actualités, les plus récentes d\'abord, quand il y en a davantage', function () {
    $term = daTerm();

    // 7 articles, du plus ancien au plus récent (pub_date croissante) : seuls les 5 plus
    // récents (num 3 à 7) doivent apparaître, les 2 plus anciens (num 1 et 2) jamais.
    $articles = [];
    for ($n = 1; $n <= 7; $n++) {
        $articles[$n] = daArticle([
            'title' => "Actualite plafond numero {$n}",
            'pub_date' => now()->subDays(7 - $n),
        ]);
        daLier($term, $articles[$n], true);
    }

    $reponse = $this->get('/glossaire/'.$term->slug);
    $reponse->assertOk();

    // Les 2 plus anciennes sont exclues par le plafond.
    $reponse->assertDontSee('Actualite plafond numero 1');
    $reponse->assertDontSee('Actualite plafond numero 2');

    // Les 5 plus récentes sont toutes présentes.
    for ($n = 3; $n <= 7; $n++) {
        $reponse->assertSee("Actualite plafond numero {$n}");
    }
});
