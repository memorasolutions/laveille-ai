<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctifs revue adversariale 2026-08-10 (Codex) sur
 * DirectoryAdminController::deriveMasterFromUpload() (design doc 2026-08-10, recadrage
 * frontend) :
 * - #3 (WYSIWYG serveur/client) : le test de hauteur se fait APRES scale(width:1200), plus sur
 *   la hauteur brute de la source - une source 600x600 (raw <= 630, mais scale-puis-teste donne
 *   1200x1200 > 630) doit desormais produire un master.
 * - #2 (master perime) : quand la hauteur resultante APRES scale ne depasse pas 630, un master
 *   EXISTANT pour ce slug doit etre supprime (jamais laisse en place perime).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
    config(['services.cloudflare.zone_id' => null, 'services.cloudflare.api_token' => null]);
});

function makeDeriveMasterTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Derive Master '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-derive-master.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

// Cree un vrai fichier JPEG (via GD) de dimensions arbitraires, enveloppe en UploadedFile de test
// (mode "test" = true, contourne is_uploaded_file() qui echouerait sur un fichier non recu en HTTP reel).
function makeDeriveMasterTestUpload(string $tmpName, int $width, int $height): UploadedFile
{
    $tmpPath = sys_get_temp_dir().'/'.$tmpName;
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, 60, 90, 130);
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $color);
    imagejpeg($img, $tmpPath, 90);
    imagedestroy($img);

    return new UploadedFile($tmpPath, 'capture.jpg', 'image/jpeg', null, true);
}

function cleanupDeriveMasterTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
}

test('#3 WYSIWYG : une source 600x600 (raw <= 630) produit un master apres scale-puis-teste (600x600 -> 1200x1200)', function () {
    $slug = 'test-derive-master-narrow-tall';
    $tool = makeDeriveMasterTestTool($slug);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $upload = makeDeriveMasterTestUpload('derive-narrow-tall.jpg', 600, 600);

    $response = $this->actingAs($user)->post(
        route('admin.directory.upload-screenshot', $tool),
        ['screenshot' => $upload]
    );

    $response->assertRedirect();
    $masterPath = public_path("screenshots/masters/{$slug}.jpg");
    expect(File::exists($masterPath))->toBeTrue();

    [, $masterHeight] = getimagesize($masterPath);
    expect($masterHeight)->toBeGreaterThan(630);

    cleanupDeriveMasterTestFiles($slug);
});

test('#2 master perime : une source large mais courte APRES scale (<=630) supprime un master EXISTANT plutot que de le laisser perime', function () {
    $slug = 'test-derive-master-stale';
    $tool = makeDeriveMasterTestTool($slug);

    // Simule un master issu d'une capture PRECEDENTE (haute, valide) deja en place pour ce slug.
    $mastersDir = public_path('screenshots/masters');
    if (! File::isDirectory($mastersDir)) {
        File::makeDirectory($mastersDir, 0755, true);
    }
    $oldMaster = imagecreatetruecolor(1200, 1400);
    imagejpeg($oldMaster, "{$mastersDir}/{$slug}.jpg", 90);
    imagedestroy($oldMaster);
    expect(File::exists(public_path("screenshots/masters/{$slug}.jpg")))->toBeTrue();

    $user = User::factory()->create();
    $user->assignRole('admin');

    // Source large et courte : 1200x300 -> scale(width:1200) ne change rien (deja 1200 de large),
    // hauteur resultante 300 <= 630 -> aucun nouveau master ne doit etre cree, ET l'ancien doit disparaitre.
    $upload = makeDeriveMasterTestUpload('derive-wide-short.jpg', 1200, 300);

    $response = $this->actingAs($user)->post(
        route('admin.directory.upload-screenshot', $tool),
        ['screenshot' => $upload]
    );

    $response->assertRedirect();
    expect(File::exists(public_path("screenshots/masters/{$slug}.jpg")))->toBeFalse();

    cleanupDeriveMasterTestFiles($slug);
});
