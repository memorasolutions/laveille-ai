<?php

/**
 * Garde-fou : la section SOURCES ne doit jamais disparaître sur l'aperçu d'un article.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * POURQUOI CE TEST EXISTE (défaut réel mesuré le 2026-09-20).
 * Sur /preview/{token} (Modules\Core\Http\Controllers\PreviewController, qui passe
 * isPreview = true), la section « Sources » d'un article était totalement absente du HTML
 * rendu, alors qu'elle est bien présente en base et qu'elle s'affiche correctement sur
 * l'article PUBLIÉ (fronttheme::blog.show, même contenu).
 *
 * Cause : en aperçu, AeoHelper::chunkContent() n'est pas exécuté (il ne l'est que hors
 * preview), donc le repli qui encadre visuellement le titre « Sources » injectait un
 * `</div>` ORPHELIN juste avant le titre - aucun `<div>` ouvrant ne l'attend dans le HTML
 * brut de l'article. GlossaryLinkifier::linkify(), appelé juste après, reparse ce HTML avec
 * DOMDocument : face à ce `</div>` non apparié, le parseur referme son wrapper interne et
 * ABANDONNE tout ce qui suit - donc toute la section Sources (noms d'auteurs, DOI, liens).
 *
 * Corrigé en enveloppant le titre ET tout ce qui le suit jusqu'à la fin du contenu dans un
 * `<div class="sources-section">` correctement FERMÉ (HTML équilibré, aucune perte possible
 * au reparse DOMDocument).
 *
 * Ce test vérifie le CONTENU des sources (un nom d'auteur ET un DOI), jamais seulement la
 * présence du titre « Sources » : un titre présent ne prouve pas que ce qu'il y a dessous
 * a survécu au reparse.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Contenu réaliste : plusieurs sections avec des h2, puis un h2 « Sources » suivi d'une
 * liste de références portant un nom d'auteur et un DOI - exactement la forme qui a fait
 * disparaître la section en aperçu (le repli s'accroche à un heading h2-h4 nommé Sources).
 */
function pvsArticleContentAvecSources(): string
{
    return '<h2>Introduction</h2>'
        .'<p>Un contenu d\'article de test, avec plusieurs paragraphes pour simuler un vrai article.</p>'
        .'<h2>Développement</h2>'
        .'<p>Encore du texte de développement, sans rapport avec les sources elles-mêmes.</p>'
        .'<h2>Sources</h2>'
        .'<ol>'
        .'<li>Handel, M. J. et al. (2026). Étude sur l\'IA en classe. <a href="https://doi.org/10.1000/labr.70018">https://doi.org/10.1000/labr.70018</a></li>'
        .'<li>Autre référence bibliographique complète.</li>'
        .'</ol>';
}

it('affiche la section Sources (auteur ET DOI) sur l\'aperçu d\'un article, pas seulement sur l\'article publié', function () {
    $article = Article::factory()->draft()->create([
        'slug' => 'article-test-preview-sources',
        'title' => 'Article test aperçu sources',
        'content' => pvsArticleContentAvecSources(),
        'preview_token' => Str::random(64),
    ]);

    // Référence : l'article PUBLIÉ affiche bel et bien la section (déjà couvert ailleurs,
    // simple garde locale pour prouver que le contenu de test est valide).
    $published = Article::factory()->published()->create([
        'slug' => 'article-test-publie-sources',
        'title' => 'Article test publié sources',
        'content' => pvsArticleContentAvecSources(),
    ]);
    $publishedResponse = $this->get('/blog/'.$published->slug);
    $publishedResponse->assertOk();
    $publishedResponse->assertSee('sources-section', false);
    $publishedResponse->assertSee('Handel, M. J.');
    $publishedResponse->assertSee('labr.70018', false);

    // Le vrai objet du test : l'APERÇU, qui empruntait le chemin fautif (chunkContent absent).
    $previewResponse = $this->get('/preview/'.$article->preview_token);

    $previewResponse->assertOk();

    // Le titre seul ne prouve rien (il pourrait survivre pendant que tout le reste tombe) :
    // on exige la présence du conteneur ET du contenu réel des sources.
    $previewResponse->assertSee('sources-section', false);
    $previewResponse->assertSee('Handel, M. J.');
    $previewResponse->assertSee('labr.70018', false);
});
