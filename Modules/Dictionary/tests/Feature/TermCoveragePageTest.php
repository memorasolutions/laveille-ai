<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve HTTP de bout en bout de la page de couverture d'un terme (ticket #2531, plan glossaire
 * étape 4) : une page par terme du lot pilote de 12 (Modules\Dictionary\Support\CoverageTerms),
 * listant TOUTES les actualités liées et APPROUVÉES d'articles PUBLIÉS. La route
 * (routes/web.php, dictionary.coverage) est restreinte par regex aux 12 slugs retenus : un terme
 * publié hors liste ne doit atteindre AUCUNE route ici (404 générique de Laravel), c'est
 * exactement ce que le test « hors liste » ci-dessous prouve - la preuve qu'on n'a pas ouvert
 * les 518 autres pages.
 *
 * Convention du module (même helpers que TermDansActualiteTest, ViewCounterDictionaryTest et
 * PublicDictionaryIndexPageTest) : pas de TermFactory ni de NewsArticleFactory, construction
 * directe des modèles. Helpers préfixés Cv (Coverage) pour éviter tout conflit de nom de
 * fonction avec les autres fichiers de test de ce module.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ───────────────────────────────────────────────────────────────────────────

function cvSource(): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => 'https://cv-source-test.exemple.com/rss'],
        ['name' => 'Source couverture test', 'language' => 'fr', 'active' => true]
    );
}

/** $slugFixe : un des 12 slugs retenus (Modules\Dictionary\Support\CoverageTerms::SLUGS). */
function cvTerm(string $slugFixe, array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();

    return Term::create(array_merge([
        'name' => [$locale => 'Terme couverture '.$slugFixe, 'fr' => 'Terme couverture'],
        'slug' => [$locale => $slugFixe, 'fr' => $slugFixe],
        'definition' => [$locale => 'Définition de test pour la page de couverture du terme.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ], $overrides));
}

function cvArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = cvSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Titre brut couverture {$i}",
        'guid' => "guid-cv-{$suffix}",
        'url' => "https://exemple.com/cv-{$suffix}",
        'description' => '',
        'summary' => "Résumé couverture {$i}",
        'slug' => "cv-test-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/** Attache un article au terme via le pivot news_article_term. */
function cvLier(Term $term, NewsArticle $article, bool $approuve = true): void
{
    $article->terms()->attach($term->id, ['source' => 'auto', 'is_approved' => $approuve]);
}

// ── Cas 1 : une des 12 pages répond 200, affiche définition + actualité + ItemList valide ──────

it('affiche la page de couverture d\'un terme du lot pilote : nom, définition, lien vers la fiche, actualité et ItemList valide', function () {
    $term = cvTerm('agent-ia');
    $article = cvArticle([
        'title' => 'Titre brut jamais affiché sur la couverture',
        'seo_title' => 'Titre optimisé affiché sur la couverture',
    ]);
    cvLier($term, $article, true);

    $reponse = $this->get('/glossaire/agent-ia/actualites');

    $reponse->assertOk();
    // Nom du terme + définition (évite une page mince, raison d'être de cette exigence).
    $reponse->assertSee($term->name);
    $reponse->assertSee('Définition de test pour la page de couverture du terme.');
    // Lien vers la fiche du terme.
    $reponse->assertSee($term->getPublicUrl(), false);
    // Actualité liée, titre optimisé affiché (repli seo_title sinon title, même convention que
    // « Dans l'actualité »), titre brut jamais affiché.
    $reponse->assertSee('Titre optimisé affiché sur la couverture');
    $reponse->assertDontSee('Titre brut jamais affiché sur la couverture');
    $reponse->assertSee(route('news.show', $article->slug), false);

    // ItemList JSON-LD valide.
    $reponse->assertSee('application/ld+json', false);
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $reponse->getContent(), $matches);
    expect($matches)->toHaveCount(2);
    $jsonLd = json_decode($matches[1], true);
    expect($jsonLd)->not->toBeNull();
    expect($jsonLd['@type'])->toBe('ItemList');
    expect($jsonLd['numberOfItems'])->toBe(1);
    expect($jsonLd['itemListElement'])->toHaveCount(1);
    expect($jsonLd['itemListElement'][0]['url'])->toBe(route('news.show', $article->slug));
});

// ── Cas 2 : un terme publié HORS liste répond 404 (preuve qu'on n'a pas ouvert 518 pages) ──────

it('répond 404 pour un terme publié HORS liste (aucune des 518 autres pages n\'existe)', function () {
    $slugHorsListe = 'terme-hors-liste-'.uniqid();
    cvTerm($slugHorsListe);

    $reponse = $this->get('/glossaire/'.$slugHorsListe.'/actualites');

    $reponse->assertNotFound();
});

// ── Cas 3 : une liaison désapprouvée n'apparaît pas ─────────────────────────────────────────────

it('n\'affiche pas une actualité dont la liaison a été désapprouvée par un administrateur', function () {
    $term = cvTerm('openai');
    $approuve = cvArticle(['title' => 'Actualite approuvee visible sur couverture']);
    $desapprouve = cvArticle(['title' => 'Actualite desapprouvee invisible sur couverture']);
    cvLier($term, $approuve, true);
    cvLier($term, $desapprouve, false);

    $reponse = $this->get('/glossaire/openai/actualites');

    $reponse->assertOk();
    $reponse->assertSee('Actualite approuvee visible sur couverture');
    $reponse->assertDontSee('Actualite desapprouvee invisible sur couverture');
});

// ── Cas 4 : un article non publié n'apparaît pas ────────────────────────────────────────────────

it('n\'affiche pas une actualité approuvée mais dont l\'article n\'est pas publié', function () {
    $term = cvTerm('google');
    $publie = cvArticle(['title' => 'Actualite publiee visible sur couverture', 'is_published' => true]);
    $brouillon = cvArticle(['title' => 'Actualite brouillon invisible sur couverture', 'is_published' => false]);
    cvLier($term, $publie, true);
    cvLier($term, $brouillon, true);

    $reponse = $this->get('/glossaire/google/actualites');

    $reponse->assertOk();
    $reponse->assertSee('Actualite publiee visible sur couverture');
    $reponse->assertDontSee('Actualite brouillon invisible sur couverture');
});
