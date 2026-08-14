<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif 2026-08-14 : le recadrage des vignettes (livraison 2026-08-10) n'est utilisable que
 * si un master existe pour l'outil, et seule une recapture posterieure a cette date en produit
 * un - aucun des outils deja publies n'en avait, la fonctionnalite etait donc invisible sur
 * l'annuaire au complet. Ces tests couvrent la commande directory:backfill-screenshot-masters
 * qui derive un master pour chaque outil publie ayant deja une vignette mais pas de master,
 * a partir de cette vignette existante (jamais d'une nouvelle capture reseau).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Cree un vrai fichier JPEG (via GD) de dimensions arbitraires directement a l'emplacement
// public/screenshots/{slug}.jpg - la ou vit la vignette deja publiee d'un outil.
function makeBackfillTestVignette(string $slug, int $width, int $height): void
{
    $dir = public_path('screenshots');
    if (! File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true);
    }
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, 60, 90, 130);
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $color);
    imagejpeg($img, "{$dir}/{$slug}.jpg", 90);
    imagedestroy($img);
}

function makeBackfillTestMaster(string $slug, int $width, int $height): void
{
    $dir = public_path('screenshots/masters');
    if (! File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true);
    }
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, 10, 20, 30);
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $color);
    imagejpeg($img, "{$dir}/{$slug}.jpg", 90);
    imagedestroy($img);
}

function makeBackfillTestTool(string $slug, string $status = 'published'): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Backfill '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-backfill.test';
    $tool->pricing = 'free';
    $tool->status = $status;
    $tool->screenshot = "screenshots/{$slug}.jpg";
    $tool->save();
    $tool->refresh();

    return $tool;
}

function cleanupBackfillTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
}

test('un outil sans master et avec une vignette suffisante obtient son master', function () {
    $slug = 'test-backfill-master-cree';
    makeBackfillTestTool($slug);
    makeBackfillTestVignette($slug, 1200, 1000);

    $masterPath = public_path("screenshots/masters/{$slug}.jpg");
    expect(File::exists($masterPath))->toBeFalse();

    Artisan::call('directory:backfill-screenshot-masters', ['--limit' => 50]);

    expect(File::exists($masterPath))->toBeTrue();
    [, $masterHeight] = getimagesize($masterPath);
    expect($masterHeight)->toBeGreaterThan(630);

    cleanupBackfillTestFiles($slug);
});

test('un outil qui a deja un master n\'est jamais touche', function () {
    $slug = 'test-backfill-master-existant';
    makeBackfillTestTool($slug);
    makeBackfillTestVignette($slug, 1200, 1000);
    makeBackfillTestMaster($slug, 1200, 1400);

    $masterPath = public_path("screenshots/masters/{$slug}.jpg");
    $hashAvant = md5_file($masterPath);

    Artisan::call('directory:backfill-screenshot-masters', ['--limit' => 50]);

    expect(File::exists($masterPath))->toBeTrue();
    expect(md5_file($masterPath))->toBe($hashAvant);

    cleanupBackfillTestFiles($slug);
});

test('une vignette trop petite est comptee et listee, sans erreur et sans master cree', function () {
    $slug = 'test-backfill-master-trop-petit';
    makeBackfillTestTool($slug);
    // 1200 de large deja (aucun scale ne change la largeur), 300 de haut <= 630 : trop petit.
    makeBackfillTestVignette($slug, 1200, 300);

    $masterPath = public_path("screenshots/masters/{$slug}.jpg");

    Artisan::call('directory:backfill-screenshot-masters', ['--limit' => 50]);
    $output = Artisan::output();

    expect(File::exists($masterPath))->toBeFalse();
    expect($output)->toContain($slug);
    expect($output)->toContain('trop petit');

    cleanupBackfillTestFiles($slug);
});

test('le mode simulation ne cree aucun fichier', function () {
    $slug = 'test-backfill-master-dry-run';
    makeBackfillTestTool($slug);
    makeBackfillTestVignette($slug, 1200, 1000);

    $masterPath = public_path("screenshots/masters/{$slug}.jpg");
    expect(File::exists($masterPath))->toBeFalse();

    Artisan::call('directory:backfill-screenshot-masters', ['--dry-run' => true, '--limit' => 50]);

    // Toujours absent : la simulation classe mais n'ecrit rien.
    expect(File::exists($masterPath))->toBeFalse();

    cleanupBackfillTestFiles($slug);
});

test('un outil non publie est ignore par le backfill', function () {
    $slug = 'test-backfill-master-non-publie';
    makeBackfillTestTool($slug, 'pending');
    makeBackfillTestVignette($slug, 1200, 1000);

    $masterPath = public_path("screenshots/masters/{$slug}.jpg");

    Artisan::call('directory:backfill-screenshot-masters', ['--limit' => 50]);

    expect(File::exists($masterPath))->toBeFalse();

    cleanupBackfillTestFiles($slug);
});

test('la limite borne le nombre d\'outils reellement traites', function () {
    $slugs = ['test-backfill-limite-a', 'test-backfill-limite-b', 'test-backfill-limite-c'];
    foreach ($slugs as $slug) {
        makeBackfillTestTool($slug);
        makeBackfillTestVignette($slug, 1200, 1000);
    }

    Artisan::call('directory:backfill-screenshot-masters', ['--limit' => 1]);

    $createdCount = 0;
    foreach ($slugs as $slug) {
        if (File::exists(public_path("screenshots/masters/{$slug}.jpg"))) {
            $createdCount++;
        }
    }
    expect($createdCount)->toBe(1);

    foreach ($slugs as $slug) {
        cleanupBackfillTestFiles($slug);
    }
});
