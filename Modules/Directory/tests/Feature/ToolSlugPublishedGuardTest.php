<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * #1985 - CommunityController::storeScreenshot() et PublicDirectoryController::storePricingReport()
 * resolvaient un outil par son slug SANS Tool::published() : une fiche brouillon/en attente/
 * archivee etait atteignable - et modifiable (screenshot pose comme image principale, signalement
 * de prix cree) - par n'importe quel utilisateur connecte qui devinait ou connaissait son slug.
 * Mesure le 2026-08-29 en base locale : 1827/2334 fiches (78 %, 1820 pending + 7 draft) etaient
 * dans un etat non publie, donc exposees par ces deux portes.
 *
 * Correctif : storeScreenshot() reutilise desormais CommunityController::findTool() (deja
 * Tool::published(), deja utilise par storeReview/storeDiscussion/storeResource/storeSuggestion
 * dans la meme classe - DRY, zero nouveau filtre invente) ; storePricingReport() ajoute
 * Tool::published() en tete de requete, meme scope que show()/visit() plus haut dans
 * PublicDirectoryController (groupe le OR existant pour qu'il reste a l'interieur du published()).
 *
 * Ni l'une ni l'autre de ces deux routes n'est accessible a un visiteur anonyme (middleware
 * 'auth' sur tout le groupe) : le scenario reel est un utilisateur AUTHENTIFIE ordinaire qui
 * devine un slug, pas un visiteur anonyme - qui est de toute facon redirige vers /connexion avant
 * d'atteindre le controleur (verifie ci-dessous, dernier test). L'acces admin/moderateur a une
 * fiche non publiee existe deja par un canal totalement distinct et non touche par ce correctif :
 * /admin/directory/{tool}/edit (liaison Eloquent par ID, jamais par slug) - couvert ici par un
 * test de non-regression.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingReport;
use Modules\Directory\Models\ToolScreenshot;
use Modules\Directory\Support\PricingCategories;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
    config(['app.locale' => 'fr_CA']);
});

function makeSlugGuardTestTool(string $slug, string $status): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Garde '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-garde.test/'.$slug;
    $tool->pricing = 'free';
    $tool->status = $status;
    $tool->save();
    $tool->refresh();

    return $tool;
}

// --- Porte 1 : CommunityController::storeScreenshot() (#1985) ---

test('storeScreenshot renvoie 404 pour une fiche pending, aucun screenshot cree', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $tool = makeSlugGuardTestTool('outil-pending-screenshot', 'pending');

    $response = $this->actingAs($user)->post(
        route('directory.screenshots.store', $tool->getTranslation('slug', 'fr_CA')),
        ['screenshot' => UploadedFile::fake()->image('capture.jpg')]
    );

    $response->assertNotFound();
    expect(ToolScreenshot::where('directory_tool_id', $tool->id)->count())->toBe(0);
});

test('storeScreenshot renvoie 404 pour une fiche draft, aucun screenshot cree', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $tool = makeSlugGuardTestTool('outil-draft-screenshot', 'draft');

    $response = $this->actingAs($user)->post(
        route('directory.screenshots.store', $tool->getTranslation('slug', 'fr_CA')),
        ['screenshot' => UploadedFile::fake()->image('capture.jpg')]
    );

    $response->assertNotFound();
    expect(ToolScreenshot::where('directory_tool_id', $tool->id)->count())->toBe(0);
});

test('storeScreenshot reste accessible pour une fiche publiee', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $tool = makeSlugGuardTestTool('outil-publie-screenshot', 'published');

    $response = $this->actingAs($user)->post(
        route('directory.screenshots.store', $tool->getTranslation('slug', 'fr_CA')),
        ['screenshot' => UploadedFile::fake()->image('capture.jpg')]
    );

    $response->assertStatus(302);
    $response->assertSessionHas('success');
    expect(ToolScreenshot::where('directory_tool_id', $tool->id)->count())->toBe(1);
});

// --- Porte 2 : PublicDirectoryController::storePricingReport() (#1985) ---

test('storePricingReport renvoie 404 pour une fiche pending, aucun signalement cree', function () {
    $user = User::factory()->create();
    $tool = makeSlugGuardTestTool('outil-pending-pricing', 'pending');

    $response = $this->actingAs($user)->post(
        route('directory.pricing-report', $tool->getTranslation('slug', 'fr_CA')),
        ['reported_pricing' => PricingCategories::FREE]
    );

    $response->assertNotFound();
    expect(ToolPricingReport::where('tool_id', $tool->id)->count())->toBe(0);
});

test('storePricingReport renvoie 404 pour une fiche archived, aucun signalement cree', function () {
    $user = User::factory()->create();
    $tool = makeSlugGuardTestTool('outil-archived-pricing', 'archived');

    $response = $this->actingAs($user)->post(
        route('directory.pricing-report', $tool->getTranslation('slug', 'fr_CA')),
        ['reported_pricing' => PricingCategories::FREE]
    );

    $response->assertNotFound();
    expect(ToolPricingReport::where('tool_id', $tool->id)->count())->toBe(0);
});

test('storePricingReport reste accessible pour une fiche publiee', function () {
    $user = User::factory()->create();
    $tool = makeSlugGuardTestTool('outil-publie-pricing', 'published');

    $response = $this->actingAs($user)->post(
        route('directory.pricing-report', $tool->getTranslation('slug', 'fr_CA')),
        ['reported_pricing' => PricingCategories::FREE]
    );

    $response->assertStatus(302);
    $response->assertSessionHas('success');
    expect(ToolPricingReport::where('tool_id', $tool->id)->count())->toBe(1);
});

// --- Un visiteur anonyme n'atteint meme pas le controleur sur ces 2 portes (middleware auth) ---

test('un visiteur anonyme est redirige vers la connexion avant meme d\'atteindre le controleur', function () {
    $tool = makeSlugGuardTestTool('outil-anonyme-garde', 'published');
    $slug = $tool->getTranslation('slug', 'fr_CA');

    $this->post(route('directory.screenshots.store', $slug))
        ->assertRedirect(route('login'));

    $this->post(route('directory.pricing-report', $slug), ['reported_pricing' => PricingCategories::FREE])
        ->assertRedirect(route('login'));
});

// --- Non-regression : l'acces admin a une fiche non publiee reste intact (canal distinct, par ID) ---

test('un superadmin previsualise toujours une fiche pending via /admin/directory/{tool}/edit', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $tool = makeSlugGuardTestTool('outil-pending-admin-edit', 'pending');

    $response = $this->actingAs($admin)->get(route('admin.directory.edit', $tool));

    $response->assertOk();
});
