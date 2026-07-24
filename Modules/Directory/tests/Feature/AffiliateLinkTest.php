<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Couvre les 5 pièces ajoutées par le plan affiliation 2026-07-24 : badge de divulgation
 * visible (2 emplacements), tracking de clic sortant (directory.visit), rel=sponsored inchangé,
 * page de divulgation, filtre admin ?affiliate=.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

// Environnement de test = sqlite :memory: (phpunit.xml), qui n'a pas la fonction MySQL FIELD()
// utilisée par PublicDirectoryController::show() pour trier les ressources (limitation
// pré-existante, indépendante de cette fonctionnalité). Polyfill scopé à ce fichier de test
// uniquement — ne touche ni le code de production ni les autres tests — pour pouvoir exercer
// la vraie route directory.show() de bout en bout (middleware + contrôleur + vue).
beforeEach(function () {
    $pdo = DB::connection()->getPdo();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->sqliteCreateFunction('FIELD', function (...$args) {
            $needle = array_shift($args);
            foreach ($args as $i => $value) {
                if ($needle === $value) {
                    return $i + 1;
                }
            }

            return 0;
        });
    }
});

function makeAffiliateTestTool(string $slug, ?string $affiliateUrl = null): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Test Affiliation '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-direct.test';
    $tool->affiliate_url = $affiliateUrl;
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

test('le badge "Lien affilié" est visible sur la fiche quand isAffiliate() est vrai', function () {
    $tool = makeAffiliateTestTool('outil-affilie-test', 'https://partenaire.test/ref/123');

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertStatus(200);
    $response->assertSee('Lien affilié', false);
    $response->assertSee(route('directory.affiliation.policy'), false);
});

test('le badge "Lien affilié" est absent sur la fiche quand isAffiliate() est faux', function () {
    $tool = makeAffiliateTestTool('outil-non-affilie-test', null);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertStatus(200);
    $response->assertDontSee('Lien affilié', false);
});

test('directory.visit incrémente outbound_clicks_count et redirige (302) vers getVisitUrl() — avec affiliate_url', function () {
    $tool = makeAffiliateTestTool('outil-visit-affilie', 'https://partenaire.test/ref/456');

    expect($tool->outbound_clicks_count)->toBe(0);

    $response = $this->get(route('directory.visit', $tool->slug));

    $response->assertStatus(302);
    $response->assertRedirect('https://partenaire.test/ref/456');

    $tool->refresh();
    expect($tool->outbound_clicks_count)->toBe(1);
    expect($tool->getVisitUrl())->toBe('https://partenaire.test/ref/456');
});

test('directory.visit incrémente outbound_clicks_count et redirige (302) vers l\'URL directe — sans affiliate_url (fallback)', function () {
    $tool = makeAffiliateTestTool('outil-visit-direct', null);

    $response = $this->get(route('directory.visit', $tool->slug));

    $response->assertStatus(302);
    $response->assertRedirect('https://exemple-direct.test');

    $tool->refresh();
    expect($tool->outbound_clicks_count)->toBe(1);
    expect($tool->getVisitUrl())->toBe('https://exemple-direct.test');
});

test('directory.visit renvoie 404 pour un slug inexistant', function () {
    config(['app.locale' => 'fr_CA']);

    $response = $this->get(route('directory.visit', 'slug-qui-nexiste-pas'));

    $response->assertStatus(404);
});

test('rel="sponsored" est présent seulement si l\'outil est affilié', function () {
    $affiliateTool = makeAffiliateTestTool('outil-rel-sponsored', 'https://partenaire.test/ref/789');
    $directTool = makeAffiliateTestTool('outil-rel-direct', null);

    $affiliateResponse = $this->get(route('directory.show', $affiliateTool->slug));
    $affiliateResponse->assertSee('rel="sponsored noopener"', false);

    $directResponse = $this->get(route('directory.show', $directTool->slug));
    $directResponse->assertSee('rel="noopener noreferrer nofollow"', false);
    // Vérification ciblée sur l'attribut rel du lien "Visiter le site" (et non le mot "sponsored"
    // en général, qui apparaît ailleurs dans le layout global — book-promo, cookies — sans lien
    // avec l'affiliation de cet outil).
    $directResponse->assertDontSee('rel="sponsored', false);
});

test('la page de divulgation /annuaire/politique-affiliation répond 200', function () {
    $response = $this->get(route('directory.affiliation.policy'));

    $response->assertStatus(200);
    $response->assertSee('affiliation', false);
});

test('le filtre admin ?affiliate=yes ne retourne que les outils avec affiliate_url', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $affiliateTool = makeAffiliateTestTool('outil-admin-filtre-oui', 'https://partenaire.test/ref/999');
    $directTool = makeAffiliateTestTool('outil-admin-filtre-non', null);

    $response = $this->actingAs($admin)->get(route('admin.directory.index', ['affiliate' => 'yes']));

    $response->assertStatus(200);
    $response->assertSee($affiliateTool->getTranslation('name', 'fr_CA'), false);
    $response->assertDontSee($directTool->getTranslation('name', 'fr_CA'), false);
});

test('le filtre admin ?affiliate=no ne retourne que les outils sans affiliate_url', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $affiliateTool = makeAffiliateTestTool('outil-admin-filtre-oui-2', 'https://partenaire.test/ref/111');
    $directTool = makeAffiliateTestTool('outil-admin-filtre-non-2', null);

    $response = $this->actingAs($admin)->get(route('admin.directory.index', ['affiliate' => 'no']));

    $response->assertStatus(200);
    $response->assertSee($directTool->getTranslation('name', 'fr_CA'), false);
    $response->assertDontSee($affiliateTool->getTranslation('name', 'fr_CA'), false);
});
