<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Design doc 2026-08-10 (recadrage frontend) - couvre le volet B (bouton public "Recadrer") de
 * bout en bout via PublicDirectoryController::show() : CA-1 (invite = aucun bouton, aucune fuite
 * du chemin master), CA-2 (moderateur + master = bouton "Recadrer"), CA-3 (moderateur sans master
 * = lien "Cadrage indisponible"), CA-4 (rendu invite inchange), CA-5 (composant focal-cropper
 * sans src statique).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
    config(['services.cloudflare.zone_id' => null, 'services.cloudflare.api_token' => null]);

    // Meme polyfill que Modules/Directory/tests/Feature/AffiliateLinkTest.php:23-41 : sqlite
    // :memory: (phpunit.xml) n'a pas la fonction MySQL FIELD() utilisee par
    // PublicDirectoryController::show() pour trier les ressources - limitation pre-existante,
    // independante de ce chantier. Polyfill scope a ce fichier, ne touche pas la production.
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

function makePublicFocalTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Focal Public '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-focal-public.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->screenshot = "screenshots/{$slug}.jpg";
    $tool->save();
    $tool->refresh();

    return $tool;
}

function createPublicFocalTestMaster(string $slug): void
{
    $mastersDir = public_path('screenshots/masters');
    if (! File::isDirectory($mastersDir)) {
        File::makeDirectory($mastersDir, 0755, true);
    }
    $img = imagecreatetruecolor(1200, 1400);
    $color = imagecolorallocate($img, 80, 120, 160);
    imagefilledrectangle($img, 0, 0, 1199, 1399, $color);
    imagejpeg($img, "{$mastersDir}/{$slug}.jpg", 90);
    imagedestroy($img);

    // La vignette publique (tool->screenshot) doit aussi exister pour que le bloc vignette
    // s'affiche (show.blade.php:408, @if($tool->screenshot)).
    $thumbDir = public_path('screenshots');
    $thumb = imagecreatetruecolor(1200, 630);
    $thumbColor = imagecolorallocate($thumb, 80, 120, 160);
    imagefilledrectangle($thumb, 0, 0, 1199, 629, $thumbColor);
    imagejpeg($thumb, "{$thumbDir}/{$slug}.jpg", 90);
    imagedestroy($thumb);
}

function cleanupPublicFocalTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
}

test('CA-1 : un invite non authentifie ne voit ni bouton Recadrer ni le chemin du master', function () {
    $slug = 'test-pub-focal-guest';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $response = $this->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertDontSee('id="rt-focal-recadrer-btn"', false);
    $response->assertDontSee('screenshots/masters/', false);
    $response->assertDontSee(__('Cadrage indisponible - capturer d\'abord'));

    cleanupPublicFocalTestFiles($slug);
});

test('CA-2 : un moderateur (moderate_tools) sur une fiche avec master voit le bouton Recadrer', function () {
    $slug = 'test-pub-focal-mod-with-master';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertSee('id="rt-focal-recadrer-btn"', false);
    $response->assertDontSee('Cadrage indisponible');

    cleanupPublicFocalTestFiles($slug);
});

test('CA-3 : un moderateur sur une fiche sans master voit le lien "Cadrage indisponible", jamais le bouton', function () {
    $slug = 'test-pub-focal-mod-no-master';
    $tool = makePublicFocalTestTool($slug); // pas de createPublicFocalTestMaster() -> pas de master
    // la vignette publique doit exister pour afficher le bloc (independant du master)
    $thumb = imagecreatetruecolor(1200, 630);
    imagejpeg($thumb, public_path("screenshots/{$slug}.jpg"), 90);
    imagedestroy($thumb);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertDontSee('id="rt-focal-recadrer-btn"', false);
    $response->assertSee('Cadrage indisponible - capturer d\'abord');

    cleanupPublicFocalTestFiles($slug);
});

test('CA-1bis : un utilisateur avec view_admin_panel seul (sans moderate_tools) ne voit ni bouton ni lien', function () {
    $slug = 'test-pub-focal-editor';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertDontSee('id="rt-focal-recadrer-btn"', false);
    $response->assertDontSee('Cadrage indisponible');

    cleanupPublicFocalTestFiles($slug);
});

test('Correctif #1 (revue adversariale) : un editor (view_admin_panel sans moderate_tools) voit le FAB mais framingMode reste desactive (comportement crop centre inchange pour lui)', function () {
    $slug = 'test-pub-focal-fab-editor';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    // Le FAB (capture assistee) reste visible pour un editor (gate view_admin_panel inchangee).
    $response->assertSee('core-capture-dialog', false);
    // Mais framingMode doit etre desactive pour ce role - sinon "composant indisponible" sans upload
    // (regression identifiee par la revue adversariale, set-focal exige moderate_tools cote serveur).
    $response->assertSee('framingMode: false', false);
    $response->assertDontSee('framingMode: true', false);

    cleanupPublicFocalTestFiles($slug);
});

test('Correctif #1 (revue adversariale) : un moderateur (moderate_tools) a framingMode active sur le FAB', function () {
    $slug = 'test-pub-focal-fab-mod';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertSee('framingMode: true', false);

    cleanupPublicFocalTestFiles($slug);
});

test('CA-4 : le rendu invite de la fiche reste fonctionnel (200, vignette presente) apres l\'ajout des variables', function () {
    $slug = 'test-pub-focal-regression';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $response = $this->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertSee($tool->getTranslation('name', 'fr_CA'));
    $response->assertSee('js-rt-screenshot-img', false);

    cleanupPublicFocalTestFiles($slug);
});

test('CA-5 : le composant focal-cropper ne contient aucun src statique pointant vers un master', function () {
    $slug = 'test-pub-focal-nosrc';
    createPublicFocalTestMaster($slug);
    $tool = makePublicFocalTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('directory.show', $tool->getTranslation('slug', 'fr_CA')));

    $response->assertOk();
    $response->assertSee('core-focal-cropper-dialog', false);
    // l'attribut src du markup statique du cropper doit rester absent (jamais un src="...masters/{slug}.jpg")
    expect($response->getContent())->not->toContain('src="'.asset("screenshots/masters/{$slug}.jpg").'"');

    cleanupPublicFocalTestFiles($slug);
});
