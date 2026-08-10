<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Design doc 2026-08-10 (screenshots annuaire - point focal) - couvre la route
 * admin.directory.set-focal de bout en bout : RBAC (meme gate can:moderate_tools que le reste du
 * groupe admin/directory, verifiee ligne reelle Modules/Directory/routes/web.php:89), CA-2
 * (clamp cote controleur), CA-5 (screenshot_locked n'empeche jamais l'action explicite de
 * l'admin), CA-10 (URL cache-bustee + purge Cloudflare ciblee seulement).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
    // Pas de config cloudflare en test -> purgeCloudflareFile() sort tot sans appel HTTP reel.
    config(['services.cloudflare.zone_id' => null, 'services.cloudflare.api_token' => null]);
});

function makeFocalFeatureTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Focal Feature '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-focal-feature.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

function createFocalFeatureTestMaster(string $slug): void
{
    $mastersDir = public_path('screenshots/masters');
    if (! File::isDirectory($mastersDir)) {
        File::makeDirectory($mastersDir, 0755, true);
    }
    $img = imagecreatetruecolor(1200, 1400);
    $color = imagecolorallocate($img, 100, 150, 200);
    imagefilledrectangle($img, 0, 0, 1199, 1399, $color);
    imagejpeg($img, "{$mastersDir}/{$slug}.jpg", 90);
    imagedestroy($img);
}

function cleanupFocalFeatureTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg.bak"));
}

test('CA-10 : invite non authentifie ne peut pas appeler set-focal (redirection, jamais 200)', function () {
    $slug = 'test-focal-feat-guest';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);

    $response = $this->post(route('admin.directory.set-focal', $tool), ['focal_y' => 100]);

    $response->assertRedirect(); // middleware auth -> redirect login, jamais d'acces
    expect($response->status())->not->toBe(200);

    cleanupFocalFeatureTestFiles($slug);
});

test('CA-10 : role editor (view_admin_panel sans moderate_tools) ne peut pas appeler set-focal (RBAC)', function () {
    $slug = 'test-focal-feat-editor';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->post(route('admin.directory.set-focal', $tool), ['focal_y' => 100]);

    $response->assertStatus(403);

    cleanupFocalFeatureTestFiles($slug);
});

test('admin peut appliquer un nouveau focal et recoit une URL cache-bustee (CA-10)', function () {
    $slug = 'test-focal-feat-admin';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->postJson(route('admin.directory.set-focal', $tool), ['focal_y' => 150]);

    $response->assertOk();
    $response->assertJson(['ok' => true, 'focal_y' => 150]);
    expect($response->json('screenshot_url'))->toContain('?v=');

    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(150);
    expect(File::exists(public_path("screenshots/{$slug}.jpg")))->toBeTrue();

    cleanupFocalFeatureTestFiles($slug);
});

test('CA-2 : une valeur focal_y negative envoyee au controleur est clampee a 0, jamais rejetee', function () {
    $slug = 'test-focal-feat-clamp-neg';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->postJson(route('admin.directory.set-focal', $tool), ['focal_y' => -500]);

    $response->assertOk();
    $response->assertJson(['ok' => true, 'focal_y' => 0]);

    cleanupFocalFeatureTestFiles($slug);
});

test('CA-2 : une valeur focal_y trop grande envoyee au controleur est clampee a 770, jamais rejetee ni 500', function () {
    $slug = 'test-focal-feat-clamp-max';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->postJson(route('admin.directory.set-focal', $tool), ['focal_y' => 999999]);

    $response->assertOk();
    $response->assertJson(['ok' => true, 'focal_y' => 770]);

    cleanupFocalFeatureTestFiles($slug);
});

test('CA-5 : screenshot_locked=true n\'empeche jamais l\'action explicite de l\'admin sur le focal', function () {
    $slug = 'test-focal-feat-locked';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);
    $tool->screenshot_locked = true;
    $tool->save();

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->postJson(route('admin.directory.set-focal', $tool), ['focal_y' => 80]);

    $response->assertOk();
    $response->assertJson(['ok' => true, 'focal_y' => 80]);

    $tool->refresh();
    expect($tool->screenshot_locked)->toBeTrue(); // le verrou lui-meme reste inchange
    expect($tool->screenshot_focal_y)->toBe(80);

    cleanupFocalFeatureTestFiles($slug);
});

test('set-focal renvoie 422 explicite (jamais 500) quand aucun master n\'existe pour l\'outil', function () {
    $slug = 'test-focal-feat-no-master';
    $tool = makeFocalFeatureTestTool($slug); // pas de createFocalFeatureTestMaster()

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->postJson(route('admin.directory.set-focal', $tool), ['focal_y' => 100]);

    $response->assertStatus(422);
    $response->assertJson(['ok' => false]);

    cleanupFocalFeatureTestFiles($slug);
});

test('CA-9 : la page edit expose les controles clavier (range + boutons haut/bas) quand un master existe', function () {
    $slug = 'test-focal-feat-ui';
    createFocalFeatureTestMaster($slug);
    $tool = makeFocalFeatureTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('admin.directory.edit', $tool));

    $response->assertOk();
    $response->assertSee('screenshot_focal_range', false);
    $response->assertSee('Repositionner la vignette');
    $response->assertDontSee('Recapture nécessaire');

    cleanupFocalFeatureTestFiles($slug);
});

test('la page edit affiche le message de recapture necessaire quand aucun master n\'existe', function () {
    $slug = 'test-focal-feat-ui-no-master';
    $tool = makeFocalFeatureTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('admin.directory.edit', $tool));

    $response->assertOk();
    $response->assertSee('Recapture nécessaire');

    cleanupFocalFeatureTestFiles($slug);
});
