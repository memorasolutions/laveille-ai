<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2522 - le wrapper legacy de Modules/Tools/routes/web.php
 * (Route::get('/outil/{slug?}', ...), introduit par P17 #235) répond LUI-MÊME par une
 * redirection générique vers /outils quand le slug ne correspond à aucun outil, au lieu de
 * laisser Laravel lever une NotFoundHttpException. Or c'est CE gestionnaire d'exception
 * (bootstrap/app.php) qui consulte la table url_redirects (redirections curatées) : un repli
 * qui répond avant que l'exception ne survienne le rend invisible pour tout le préfixe /outil/.
 * Mesuré en production : 2 redirections curatées sur ce préfixe étaient donc mortes d'avril
 * 2026 au 2026-09-13.
 *
 * Ces tests prouvent les trois comportements attendus après correctif :
 *   1. une adresse inexistante QUI A une redirection curatée active suit cette redirection
 *      (bon statut, compteur de visites incrémenté) ;
 *   2. une adresse inexistante SANS redirection curatée retombe toujours sur /outils
 *      (témoin négatif - sans lui, un correctif qui redirigerait tout passerait pour un succès) ;
 *   3. un outil dont le slug existe réellement redirige toujours vers sa propre fiche
 *      (/outils/{slug}) et n'est jamais détourné, même quand une redirection curatée porte
 *      exactement sur son adresse /outil/{slug}.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SEO\Models\UrlRedirect;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Helper préfixé tsrf (Tool Singular Redirect Fallback) pour éviter tout conflit inter-fichiers
// (Pest charge tous les fichiers de tests dans un seul processus).

function tsrfTool(string $slug): Tool
{
    return Tool::create([
        'name' => 'Outil TSRF '.$slug,
        'slug' => $slug,
        'description' => 'Outil de test pour la redirection singuliere legacy.',
        'icon' => '🛠️',
        'sort_order' => 999,
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);
}

it('redirige une adresse /outil inexistante vers la cible de sa redirection curatee et incremente les visites', function () {
    $slug = 'tsrf-slug-inexistant-'.uniqid();
    $redirect = UrlRedirect::create([
        'from_url' => '/outil/'.$slug,
        'to_url' => '/outils/cible-curatee-tsrf',
        'status_code' => 301,
        'is_active' => true,
    ]);

    expect($redirect->hits)->toBe(0);

    $response = $this->get('/outil/'.$slug);

    $response->assertRedirect('/outils/cible-curatee-tsrf');
    $response->assertStatus(301);
    expect($redirect->refresh()->hits)->toBe(1);
});

it('retombe sur le repli d\'origine /outils quand aucune redirection curatee ne correspond (temoin negatif)', function () {
    // Sans ce temoin, un correctif qui redirigerait tout finirait par passer pour un succes.
    $slug = 'tsrf-slug-sans-redirection-'.uniqid();

    $response = $this->get('/outil/'.$slug);

    $response->assertRedirect('/outils');
    $response->assertStatus(301);
});

it('redirige toujours un outil existant vers sa propre fiche, sans jamais le detourner meme si une redirection curatee porte sur la meme adresse', function () {
    $slug = 'tsrf-slug-existant-'.uniqid();
    tsrfTool($slug);

    // Redirection curatée piégée volontairement sur l'adresse /outil/{slug} de l'outil réel :
    // si le correctif interrogeait la table AVANT de vérifier l'existence de l'outil, ce test
    // échouerait par une redirection vers ailleurs au lieu de la fiche réelle de l'outil.
    UrlRedirect::create([
        'from_url' => '/outil/'.$slug,
        'to_url' => '/outils/ailleurs',
        'status_code' => 301,
        'is_active' => true,
    ]);

    $response = $this->get('/outil/'.$slug);

    $response->assertRedirect('/outils/'.$slug);
    $response->assertStatus(301);
});
