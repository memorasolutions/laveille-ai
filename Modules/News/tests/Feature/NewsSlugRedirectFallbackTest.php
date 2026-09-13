<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2522 - le wrapper « intelligent » de Modules/News/routes/web.php
 * (Route::get('/actualites/{slug}', ...), introduit par P17 #235) répond LUI-MÊME par une
 * redirection générique vers /actualites quand le slug ne correspond à aucune fiche, au lieu
 * de laisser Laravel lever une NotFoundHttpException. Or c'est CE gestionnaire d'exception
 * (bootstrap/app.php) qui consulte la table url_redirects (redirections curatées) : un repli
 * qui répond avant que l'exception ne survienne le rend invisible pour tout le préfixe
 * /actualites/. Mesuré en production : 242 redirections curatées sur ce préfixe étaient donc
 * mortes d'avril 2026 au 2026-09-13, sans aucune erreur visible - le visiteur atterrissait
 * silencieusement sur /actualites au lieu de sa destination.
 *
 * Ces tests prouvent les trois comportements attendus après correctif :
 *   1. une adresse inexistante QUI A une redirection curatée active suit cette redirection
 *      (bon statut, compteur de visites incrémenté) ;
 *   2. une adresse inexistante SANS redirection curatée retombe toujours sur /actualites
 *      (témoin négatif - sans lui, un correctif qui redirigerait tout passerait pour un succès) ;
 *   3. une fiche qui existe réellement affiche toujours sa fiche et n'est jamais détournée,
 *      même quand une redirection curatée porte exactement sur son adresse.
 */

use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\SEO\Models\UrlRedirect;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Helpers préfixés nsrf (News Slug Redirect Fallback) pour éviter tout conflit inter-fichiers
// (Pest charge tous les fichiers de tests dans un seul processus).

function nsrfSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source NSRF',
        'url' => 'https://nsrf-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function nsrfArticle(string $slug): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => nsrfSource()->id,
        'title' => 'Fiche NSRF '.$slug,
        'guid' => 'guid-nsrf-'.$slug,
        'url' => 'https://exemple.com/'.$slug,
        'description' => '',
        'slug' => $slug,
        'summary' => 'Un résumé publié, suffisant pour que la page se rende.',
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

it('redirige une adresse actualites inexistante vers la cible de sa redirection curatee et incremente les visites', function () {
    $slug = 'nsrf-slug-inexistant-'.uniqid();
    $redirect = UrlRedirect::create([
        'from_url' => '/actualites/'.$slug,
        'to_url' => '/actualites/cible-curatee-nsrf',
        'status_code' => 301,
        'is_active' => true,
    ]);

    expect($redirect->hits)->toBe(0);

    $response = $this->get('/actualites/'.$slug);

    $response->assertRedirect('/actualites/cible-curatee-nsrf');
    $response->assertStatus(301);
    expect($redirect->refresh()->hits)->toBe(1);
});

it('retombe sur le repli d\'origine /actualites quand aucune redirection curatee ne correspond (temoin negatif)', function () {
    // Sans ce temoin, un correctif qui redirigerait tout finirait par passer pour un succes.
    $slug = 'nsrf-slug-sans-redirection-'.uniqid();

    $response = $this->get('/actualites/'.$slug);

    $response->assertRedirect('/actualites');
    $response->assertStatus(301);
});

it('affiche toujours la fiche existante et ne la detourne jamais, meme si une redirection curatee porte sur la meme adresse', function () {
    $slug = 'nsrf-slug-existant-'.uniqid();
    $article = nsrfArticle($slug);

    // Redirection curatée piégée volontairement sur l'adresse de la fiche réelle : si le
    // correctif interrogeait la table AVANT de vérifier l'existence de l'article, ce test
    // échouerait par une redirection au lieu d'un rendu de la fiche.
    UrlRedirect::create([
        'from_url' => '/actualites/'.$slug,
        'to_url' => '/actualites/ailleurs',
        'status_code' => 301,
        'is_active' => true,
    ]);

    $response = $this->get('/actualites/'.$slug);

    $response->assertOk();
    $response->assertSee($article->title, false);
});
