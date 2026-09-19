<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Facades\Settings;
use Modules\Tools\Models\Tool;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Mode "maintenance" (construction_mode = 'maintenance', 2026-09-19) : ferme un outil précis au
// public (503 + Retry-After, JAMAIS de noindex) pendant une réécriture, sans jamais toucher au
// reste du site (pas de php artisan down) et sans exiger de déploiement (bascule via
// tools:maintenance {slug} --on|--off, état en base). Réutilise le gate is_under_construction /
// Tool::isAccessibleTo déjà en place pour constructeur-prompts (modes construction/revision,
// couverts par ConstructeurPromptsGateTest.php) - ces tests couvrent UNIQUEMENT le 3e mode ajouté
// ici, avec son propre outil de test pour ne dépendre d'aucune fixture d'un autre fichier.

function createMaintenanceTestTool(array $overrides = []): Tool
{
    return Tool::create(array_merge([
        'name' => 'Outil de test maintenance',
        'slug' => 'outil-test-maintenance',
        'description' => 'Outil utilisé uniquement par les tests du mode maintenance.',
        'icon' => '🧪',
        'is_active' => true,
        'is_under_construction' => false,
        'construction_mode' => 'construction',
        'category' => 'productivite',
    ], $overrides));
}

it('serves the real tool with 200 when maintenance is off', function () {
    createMaintenanceTestTool();

    $response = $this->get('/outils/outil-test-maintenance');

    $response->assertOk();
    $response->assertDontSee('id="uc-title"', escape: false);
});

it('serves a 503 with a Retry-After header and the travaux page to an anonymous visitor when maintenance is on, WITHOUT noindex', function () {
    // APP_NOINDEX=true en local (.env) force "noindex" sur TOUT le site, sans rapport avec
    // cette fonctionnalité - neutralisé ici pour isoler et vérifier le vrai signal de CETTE
    // page (l'absence de @section('page_noindex') en mode maintenance).
    config(['app.noindex' => false]);

    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    $response = $this->get('/outils/outil-test-maintenance');

    $response->assertStatus(503);
    $response->assertHeader('Retry-After');
    $response->assertSee('id="uc-title"', escape: false);
    $response->assertSee('Voir tous les outils', escape: false);

    // Garde-fou anti-régression SEO explicite : jamais de noindex combiné au 503 (sinon la page
    // déjà indexée sortirait de l'index - le 503 + Retry-After suffit comme signal).
    $response->assertDontSee('content="noindex', escape: false);
    $response->assertSee('content="index, follow', escape: false);
});

// Régression mesurée le 2026-09-19 (coordinateur, sur la page RÉELLEMENT servie de
// constructeur-prompts) : le test ci-dessus force config(['app.noindex' => false]) pour isoler
// le mécanisme @section('page_noindex') - ce qui masquait exactement le vrai bug. Le layout
// partagé (fronttheme::layouts.master) pose SA PROPRE balise noindex dès que le drapeau SITE
// ENTIER config('app.noindex') est vrai (ex. APP_NOINDEX=true dans ce .env local, absent de
// .env.production), et cette branche est vérifiée AVANT page_noindex - elle gagnait donc
// TOUJOURS, quel que soit notre @unless. Ce test-ci reproduit fidèlement cette condition
// (app.noindex=true) et prouve que PublicToolController::show() neutralise bien la balise à la
// SORTIE, quelle qu'en soit la source - le seul test qui aurait dû rougir avant le correctif.
it('never leaks a noindex meta tag or an X-Robots-Tag header in maintenance mode, even when the SITE-WIDE app.noindex flag is true (regression 2026-09-19)', function () {
    config(['app.noindex' => true]);

    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    $response = $this->get('/outils/outil-test-maintenance');

    $response->assertStatus(503);
    $response->assertHeader('Retry-After');
    expect($response->headers->has('X-Robots-Tag'))->toBeFalse();

    $html = $response->getContent();
    expect(mb_stripos($html, 'noindex'))->toBe(false);
    $response->assertSee('content="index, follow', escape: false);
});

// Contre-épreuve indispensable (l'autre sens) : le correctif ci-dessus ne doit JAMAIS retirer le
// noindex des 2 modes historiques (construction/revision), qui servent un 200 et n'ont aucune
// raison d'être indexés - seul le mode maintenance a changé de comportement. app.noindex=false ici
// pour isoler précisément le mécanisme @section('page_noindex'), propre à ces 2 modes.
it('still marks construction and revision modes as noindex (non-regression - only maintenance mode changed)', function () {
    config(['app.noindex' => false]);

    createMaintenanceTestTool([
        'slug' => 'outil-test-construction',
        'is_under_construction' => true,
        'construction_mode' => 'construction',
    ]);
    createMaintenanceTestTool([
        'slug' => 'outil-test-revision',
        'is_under_construction' => true,
        'construction_mode' => 'revision',
    ]);

    $construction = $this->get('/outils/outil-test-construction');
    $construction->assertOk();
    $construction->assertSee('content="noindex, follow', escape: false);

    $revision = $this->get('/outils/outil-test-revision');
    $revision->assertOk();
    $revision->assertSee('content="noindex, follow', escape: false);
});

it('serves the real tool (200, not the travaux page) to a logged-in superadmin while maintenance is on', function () {
    Role::firstOrCreate(['name' => 'super_admin']);

    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get('/outils/outil-test-maintenance');

    $response->assertOk();
    $response->assertDontSee('id="uc-title"', escape: false);

    // Bandeau "mode travaux, visible par toi seul" - présent pour le contournement, jamais pour
    // le grand public (couvert par le test 503 ci-dessus, qui ne le voit pas non plus).
    $response->assertSee('Mode travaux', escape: false);
});

it('serves the real tool (200) with a valid preview token in the URL and sets a long-lived bypass cookie; an invalid token still gets a 503', function () {
    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    Settings::set(Tool::MAINTENANCE_PREVIEW_TOKEN_SETTING, 'jeton-de-test-valide-1234', 'string', 'secrets');

    $ok = $this->get('/outils/outil-test-maintenance?'.Tool::MAINTENANCE_PREVIEW_QUERY.'=jeton-de-test-valide-1234');

    $ok->assertOk();
    $ok->assertDontSee('id="uc-title"', escape: false);
    $ok->assertCookie(Tool::MAINTENANCE_PREVIEW_COOKIE, 'jeton-de-test-valide-1234');

    $bad = $this->get('/outils/outil-test-maintenance?'.Tool::MAINTENANCE_PREVIEW_QUERY.'=un-jeton-invalide');

    $bad->assertStatus(503);
    $bad->assertSee('id="uc-title"', escape: false);
});

it('lets the bypass cookie alone (without the URL token) keep granting access on a later visit', function () {
    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    Settings::set(Tool::MAINTENANCE_PREVIEW_TOKEN_SETTING, 'jeton-cookie-1234', 'string', 'secrets');

    $response = $this->withCookie(Tool::MAINTENANCE_PREVIEW_COOKIE, 'jeton-cookie-1234')
        ->get('/outils/outil-test-maintenance');

    $response->assertOk();
    $response->assertDontSee('id="uc-title"', escape: false);
});

it('does not affect another tool while the first one is in maintenance (non-regression)', function () {
    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    Tool::firstOrCreate(['slug' => 'minuteur-visuel'], [
        'name' => 'Minuteur visuel',
        'description' => 'Test',
        'icon' => '⏱️',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertOk();
    $response->assertDontSee('id="uc-title"', escape: false);
});

it('lists the tools index normally while one tool is in maintenance (non-regression)', function () {
    createMaintenanceTestTool([
        'is_under_construction' => true,
        'construction_mode' => 'maintenance',
    ]);

    $response = $this->get('/outils');

    $response->assertOk();
});
