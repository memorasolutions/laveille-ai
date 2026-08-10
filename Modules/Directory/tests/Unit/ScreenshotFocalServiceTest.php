<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Design doc 2026-08-10 (screenshots annuaire - point focal) - couvre CA-1 (correspondance
 * pixel du crop) et le bornage CA-2 au niveau du service (le bornage cote controleur est couvert
 * separement par ScreenshotAdminFocalTest.php). Ecart assume vs la spec (a signaler) : la fixture
 * JPEG n'est pas un binaire pre-commite dans Fixtures/, elle est generee de facon deterministe par
 * GD dans un helper local - evite un blob binaire opaque dans le depot pour un gain de couverture
 * identique (deux bandes de couleur franche, alignement choisi pour ne jamais echantillonner pres
 * d'une frontiere JPEG 8x8).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotFocalService;

uses(Tests\TestCase::class, RefreshDatabase::class);

const FOCAL_TEST_MASTER_HEIGHT = 1400;

const FOCAL_TEST_MASTER_WIDTH = 1200;

const FOCAL_TEST_SPLIT_Y = 700;

function makeFocalUnitTestTool(string $slug, int $focalY = 0): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Focal Unit '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-focal-unit.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->screenshot_focal_y = $focalY;
    $tool->save();
    $tool->refresh();

    return $tool;
}

/**
 * Master 1200x1400 en deux bandes de couleur franche (rouge 0-699, bleu 700-1399), suffisamment
 * eloignees de la frontiere y=700 pour que tout echantillonnage a >= 20px de la frontiere soit
 * fiable meme apres compression JPEG.
 */
function createFocalUnitTestMaster(string $slug): string
{
    $mastersDir = public_path('screenshots/masters');
    if (! File::isDirectory($mastersDir)) {
        File::makeDirectory($mastersDir, 0755, true);
    }
    $path = "{$mastersDir}/{$slug}.jpg";

    $img = imagecreatetruecolor(FOCAL_TEST_MASTER_WIDTH, FOCAL_TEST_MASTER_HEIGHT);
    $red = imagecolorallocate($img, 220, 20, 20);
    $blue = imagecolorallocate($img, 20, 20, 220);
    imagefilledrectangle($img, 0, 0, FOCAL_TEST_MASTER_WIDTH - 1, FOCAL_TEST_SPLIT_Y - 1, $red);
    imagefilledrectangle($img, 0, FOCAL_TEST_SPLIT_Y, FOCAL_TEST_MASTER_WIDTH - 1, FOCAL_TEST_MASTER_HEIGHT - 1, $blue);
    imagejpeg($img, $path, 95);
    imagedestroy($img);

    return $path;
}

function cleanupFocalUnitTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg.tmp"));
}

test('CA-1 : deriveThumbnail() produit une vignette 1200x630 qui correspond a la tranche attendue du master', function () {
    $slug = 'test-focal-unit-ca1';
    createFocalUnitTestMaster($slug);
    // focal_y = 200 (valeur d'exemple du design doc) : le haut de la vignette (master y=200) doit
    // rester dans la bande rouge, le bas (master y = 200+629 = 829) doit deja etre dans la bande bleue.
    $tool = makeFocalUnitTestTool($slug, 200);

    $service = new ScreenshotFocalService();
    $ok = $service->deriveThumbnail($tool);

    expect($ok)->toBeTrue();

    $thumbPath = public_path("screenshots/{$slug}.jpg");
    expect(File::exists($thumbPath))->toBeTrue();

    $manager = new ImageManager(new ImageGdDriver());
    $thumb = $manager->read($thumbPath);

    expect($thumb->width())->toBe(1200);
    expect($thumb->height())->toBe(630);

    // Haut de la vignette (row 0) = master row 200 -> rouge.
    $top = $thumb->pickColor(600, 0)->toArray();
    expect($top[0])->toBeGreaterThan(150); // canal rouge dominant
    expect($top[2])->toBeLessThan(100);    // canal bleu faible

    // Bas de la vignette (row 629) = master row 829 -> bleu.
    $bottom = $thumb->pickColor(600, 629)->toArray();
    expect($bottom[2])->toBeGreaterThan(150); // canal bleu dominant
    expect($bottom[0])->toBeLessThan(100);    // canal rouge faible

    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(200);

    cleanupFocalUnitTestFiles($slug);
});

test('CA-2 (service) : un focal_y hors bornes est reborne a [0, hauteur_master - 630] avant le crop', function () {
    $slug = 'test-focal-unit-ca2';
    createFocalUnitTestMaster($slug);
    // Valeur volontairement hors bornes (le controleur clampe deja a 770, mais le service ne fait
    // jamais confiance a l'attribut du modele non plus - defense en profondeur, section 6 du design doc).
    $tool = makeFocalUnitTestTool($slug, 999999);

    $service = new ScreenshotFocalService();
    $ok = $service->deriveThumbnail($tool);

    expect($ok)->toBeTrue();

    $tool->refresh();
    // Borne haute = hauteur_master (1400) - 630 = 770.
    expect($tool->screenshot_focal_y)->toBe(FOCAL_TEST_MASTER_HEIGHT - 630);

    $manager = new ImageManager(new ImageGdDriver());
    $thumb = $manager->read(public_path("screenshots/{$slug}.jpg"));
    // A la borne haute (focal_y=770), la vignette entiere (770..1400) est dans la bande bleue.
    $top = $thumb->pickColor(600, 0)->toArray();
    expect($top[2])->toBeGreaterThan(150);

    cleanupFocalUnitTestFiles($slug);
});

test('CA-2 (service) : une valeur negative est reborne a 0', function () {
    $slug = 'test-focal-unit-ca2-negative';
    createFocalUnitTestMaster($slug);
    $tool = makeFocalUnitTestTool($slug, 0);
    $tool->screenshot_focal_y = -50; // simule un attribut deja hors bornes (contournement direct du modele)

    $service = new ScreenshotFocalService();
    $ok = $service->deriveThumbnail($tool);

    expect($ok)->toBeTrue();
    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(0);

    cleanupFocalUnitTestFiles($slug);
});

test('deriveThumbnail() renvoie false sans exception quand le master est introuvable', function () {
    $slug = 'test-focal-unit-no-master';
    $tool = makeFocalUnitTestTool($slug, 100);

    $service = new ScreenshotFocalService();
    $ok = $service->deriveThumbnail($tool);

    expect($ok)->toBeFalse();

    cleanupFocalUnitTestFiles($slug);
});
