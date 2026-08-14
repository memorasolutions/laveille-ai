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
 *
 * Correctif 2026-08-14 (perte de travail admin, defaut releve en production) : la version
 * 2026-08-10 de deriveMasterFromUpload() SUPPRIMAIT un master existant (et par ricochet rendait
 * inutile le point focal regle dessus) des qu'une recapture, une fois mise a l'echelle, n'atteignait
 * pas la hauteur minimale requise. Principe directeur desormais applique : ne jamais detruire le
 * travail de l'administrateur.
 * - #2 (ex-"master perime", INVERSE le 2026-08-14) : quand la hauteur resultante APRES scale ne
 *   depasse pas 630, un master EXISTANT (et le point focal associe) doit rester intact - jamais
 *   supprime ni reinitialise. L'ecart devient visible via Tool::screenshot_master_stale, jamais
 *   tranche automatiquement.
 * - Nouveau : quand aucun master n'existait avant et que la capture est trop courte, rien ne change
 *   (comportement inchange, aucun master cree, aucun marqueur pose).
 * - Nouveau : quand un NOUVEAU master valide est effectivement derive, le point focal est
 *   reinitialise a 0 et le marqueur de peremption est leve - mais seulement dans ce cas precis.
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

    // Un master valide vient d'etre derive : le point focal repart de 0 et aucun ecart n'est signale.
    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(0);
    expect((bool) $tool->screenshot_master_stale)->toBeFalse();

    cleanupDeriveMasterTestFiles($slug);
});

test('#2 zero perte de travail : une recapture trop courte CONSERVE le master ET le point focal existants (jamais supprimes)', function () {
    $slug = 'test-derive-master-stale';
    $tool = makeDeriveMasterTestTool($slug);

    // Simule un master issu d'une capture PRECEDENTE (haute, valide) deja en place pour ce slug,
    // avec un point focal deja regle a la main par l'administrateur (travail humain a proteger).
    $mastersDir = public_path('screenshots/masters');
    if (! File::isDirectory($mastersDir)) {
        File::makeDirectory($mastersDir, 0755, true);
    }
    $masterPath = "{$mastersDir}/{$slug}.jpg";
    $oldMaster = imagecreatetruecolor(1200, 1400);
    imagejpeg($oldMaster, $masterPath, 90);
    imagedestroy($oldMaster);
    expect(File::exists($masterPath))->toBeTrue();
    $originalMasterHash = md5_file($masterPath);

    $tool->screenshot_focal_y = 250; // reglage manuel prealable de l'administrateur
    $tool->saveQuietly();

    $user = User::factory()->create();
    $user->assignRole('admin');

    // Source large et courte : 1200x300 -> scale(width:1200) ne change rien (deja 1200 de large),
    // hauteur resultante 300 <= 630 -> aucun nouveau master ne doit etre cree, l'ancien doit rester
    // intact (memes octets) et le point focal regle a la main ne doit pas bouger.
    $upload = makeDeriveMasterTestUpload('derive-wide-short.jpg', 1200, 300);

    $response = $this->actingAs($user)->post(
        route('admin.directory.upload-screenshot', $tool),
        ['screenshot' => $upload]
    );

    $response->assertRedirect();

    // Le master existant n'a jamais ete touche (ni supprime, ni ecrase).
    expect(File::exists($masterPath))->toBeTrue();
    expect(md5_file($masterPath))->toBe($originalMasterHash);

    // Le point focal regle par l'administrateur n'a pas bouge.
    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(250);

    // L'ecart entre le master conserve et la capture courante est rendu visible cote admin.
    expect((bool) $tool->screenshot_master_stale)->toBeTrue();

    cleanupDeriveMasterTestFiles($slug);
});

test('aucun master prealable + capture trop courte : rien n\'est cree, comportement inchange', function () {
    $slug = 'test-derive-master-none-before';
    $tool = makeDeriveMasterTestTool($slug);

    $masterPath = public_path("screenshots/masters/{$slug}.jpg");
    expect(File::exists($masterPath))->toBeFalse();

    $user = User::factory()->create();
    $user->assignRole('admin');

    $upload = makeDeriveMasterTestUpload('derive-none-before.jpg', 1200, 300);

    $response = $this->actingAs($user)->post(
        route('admin.directory.upload-screenshot', $tool),
        ['screenshot' => $upload]
    );

    $response->assertRedirect();
    expect(File::exists($masterPath))->toBeFalse();

    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(0);
    expect((bool) $tool->screenshot_master_stale)->toBeFalse();

    cleanupDeriveMasterTestFiles($slug);
});

test('une nouvelle capture valide APRES un ecart signale leve le marqueur de peremption et reinitialise le focal', function () {
    $slug = 'test-derive-master-resolve-stale';
    $tool = makeDeriveMasterTestTool($slug);

    // Etat de depart : master existant, focal regle, ecart deja signale par une recapture precedente trop courte.
    $mastersDir = public_path('screenshots/masters');
    if (! File::isDirectory($mastersDir)) {
        File::makeDirectory($mastersDir, 0755, true);
    }
    $masterPath = "{$mastersDir}/{$slug}.jpg";
    $oldMaster = imagecreatetruecolor(1200, 1400);
    imagejpeg($oldMaster, $masterPath, 90);
    imagedestroy($oldMaster);

    $tool->screenshot_focal_y = 400;
    $tool->screenshot_master_stale = true;
    $tool->saveQuietly();

    $user = User::factory()->create();
    $user->assignRole('admin');

    // Nouvelle capture haute et valide : doit remplacer le master, reinitialiser le focal et lever l'ecart.
    $upload = makeDeriveMasterTestUpload('derive-tall-valid.jpg', 1200, 1000);

    $response = $this->actingAs($user)->post(
        route('admin.directory.upload-screenshot', $tool),
        ['screenshot' => $upload]
    );

    $response->assertRedirect();
    expect(File::exists($masterPath))->toBeTrue();

    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(0);
    expect((bool) $tool->screenshot_master_stale)->toBeFalse();

    cleanupDeriveMasterTestFiles($slug);
});
