<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Design doc 2026-08-10 (screenshots annuaire - point focal et robustesse) - couvre CA-3, CA-4,
 * CA-5 (au niveau service, complementaire du CA-5 controleur deja couvert par
 * ScreenshotAdminFocalTest.php), CA-6 et CA-8. Simule le retour JSON du script Node via
 * Process::fake() (Illuminate\Support\Facades\Process) - aucune dependance a Puppeteer reellement
 * installe en CI, conforme a la strategie de tests de la section 8 du design doc.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // ScreenshotService::isAvailable() lit services.browsershot.node_path (BROWSERSHOT_NODE_PATH)
    // - absent de phpunit.xml, la cle existe mais vaut null (le defaut de config() ne s'applique
    // pas a une cle presente mais nulle). Sans ce forcage, capture() ne depasserait jamais son
    // premier garde-fou (Node introuvable) et ces tests ne testeraient rien. Process::fake()
    // intercepte de toute facon l'execution reelle : seul file_exists() doit renvoyer true, d'ou
    // un chemin de fichier reellement present sur TOUT environnement (local ou CI), plutot qu'un
    // chemin Node absolu specifique a cette machine.
    config(['services.browsershot.node_path' => base_path('artisan')]);
});

function makeOverwriteGuardTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Guard '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-guard.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

/**
 * Simule le retour du script capture-screenshot.cjs : Process::fake() intercepte l'appel reel
 * (Node/Puppeteer jamais execute) et $writeTempFile (optionnel) recree l'effet de bord attendu
 * (ecriture du fichier temporaire que le vrai script aurait produit) avant de renvoyer le JSON.
 */
function fakeCaptureProcess(array|callable $json, ?callable $writeTempFile = null): void
{
    Process::fake(function (\Illuminate\Process\PendingProcess $process) use ($json, $writeTempFile) {
        $command = $process->command;
        $tempPath = is_array($command) ? end($command) : null;
        if ($tempPath && $writeTempFile) {
            $writeTempFile($tempPath);
        }

        $payload = is_callable($json) ? $json($tempPath) : $json;

        return Process::result(output: json_encode($payload));
    });
}

function writeUniformJpeg(string $path, int $width = 1200, int $height = 630, array $rgb = [250, 250, 250]): void
{
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $color);
    imagejpeg($img, $path, 90);
    imagedestroy($img);
}

/**
 * Bandes de couleurs franches (non-uniforme) avec deux marqueurs extremes (noir en haut, rouge en
 * bas) pour verifier qu'un contain+flou (CA-6) ne coupe jamais le sujet.
 */
function writeBandedMarkerJpeg(string $path, int $size = 800): void
{
    $img = imagecreatetruecolor($size, $size);
    $palette = [[230, 25, 25], [25, 230, 25], [25, 25, 230], [230, 230, 25], [25, 230, 230], [230, 25, 230], [128, 128, 128], [255, 165, 0]];
    $bandHeight = (int) ceil($size / count($palette));
    foreach ($palette as $i => [$r, $g, $b]) {
        $color = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, $i * $bandHeight, $size - 1, min(($i + 1) * $bandHeight - 1, $size - 1), $color);
    }
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefilledrectangle($img, (int) ($size * 0.4), 0, (int) ($size * 0.6), 60, $black); // marqueur haut
    $red = imagecolorallocate($img, 235, 15, 15);
    imagefilledrectangle($img, (int) ($size * 0.4), $size - 60, (int) ($size * 0.6), $size - 1, $red); // marqueur bas
    imagejpeg($img, $path, 92);
    imagedestroy($img);
}

/**
 * Bandes de couleurs franches aux dimensions EXACTES 1200x630 attendues par
 * isValidScreenshotContent() pour method='screenshot' (contrairement a writeBandedMarkerJpeg(),
 * carree et pensee pour le fallback og:image) - reutilise le motif deja en place dans le test
 * "une nouvelle capture valide..." (CA-3), extrait ici car servant desormais a plusieurs tests du
 * garde-fou navigation (2026-08-30).
 */
function writeBanded1200x630Jpeg(string $path): void
{
    $img = imagecreatetruecolor(1200, 630);
    $palette = [[230, 25, 25], [25, 230, 25], [25, 25, 230], [230, 230, 25], [25, 230, 230], [230, 25, 230]];
    $band = (int) ceil(630 / count($palette));
    foreach ($palette as $i => [$r, $g, $b]) {
        $color = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, $i * $band, 1199, min(($i + 1) * $band - 1, 629), $color);
    }
    imagejpeg($img, $path, 90);
    imagedestroy($img);
}

function scanForColorMatch(ImageInterface $image, callable $matcher, int $stride = 10): bool
{
    $w = $image->width();
    $h = $image->height();
    for ($y = 0; $y < $h; $y += $stride) {
        for ($x = 0; $x < $w; $x += $stride) {
            [$r, $g, $b] = $image->pickColor($x, $y)->toArray();
            if ($matcher($r, $g, $b)) {
                return true;
            }
        }
    }

    return false;
}

function cleanupOverwriteGuardTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg.bak"));
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/_tmp_{$slug}.jpg"));
    @unlink(public_path("screenshots/_tmp_{$slug}.jpg.master.jpg"));
}

test('CA-4 : une image quasi-uniforme (>98% meme teinte) ne remplace jamais une vignette existante', function () {
    $slug = 'test-guard-ca4-uniform';
    $tool = makeOverwriteGuardTestTool($slug);
    $existingPath = public_path("screenshots/{$slug}.jpg");
    writeUniformJpeg($existingPath, 1200, 630, [10, 80, 10]); // existant distinctif (vert fonce)

    fakeCaptureProcess(
        // final_url/post_redirect_url conformes au domaine attendu : le rejet mesure par ce test
        // doit rester celui du contenu quasi-uniforme (ci-dessous), jamais celui du garde-fou de
        // navigation (2026-08-30) qui s'evalue AVANT - sans quoi ce test resterait vert sans plus
        // exercer isValidScreenshotContent().
        ['success' => true, 'method' => 'screenshot', 'size' => 25000, 'goto_status' => 'loaded', 'final_url' => 'https://exemple-guard.test/', 'post_redirect_url' => 'https://exemple-guard.test/'],
        fn (string $tempPath) => writeUniformJpeg($tempPath, 1200, 630, [245, 245, 245]) // nouvelle capture blanche quasi-uniforme
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();

    $manager = new ImageManager(new ImageGdDriver());
    $stillExisting = $manager->read($existingPath);
    $pixel = $stillExisting->pickColor(600, 300)->toArray();
    expect($pixel[1])->toBeGreaterThan($pixel[0]); // toujours la teinte verte de l'existant, jamais remplacee

    cleanupOverwriteGuardTestFiles($slug);
});

test('CA-4 : un signal blocked=true du script (meme avec success=true) ne remplace jamais une vignette existante', function () {
    $slug = 'test-guard-ca4-blocked';
    $tool = makeOverwriteGuardTestTool($slug);
    $existingPath = public_path("screenshots/{$slug}.jpg");
    writeUniformJpeg($existingPath, 1200, 630, [10, 10, 90]); // existant distinctif (bleu fonce)

    fakeCaptureProcess(
        ['success' => true, 'method' => 'screenshot', 'size' => 25000, 'blocked' => true, 'goto_status' => 'blocked'],
        fn (string $tempPath) => writeBandedMarkerJpeg($tempPath, 630) // contenu non-uniforme, serait valide sans le flag blocked
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();

    $manager = new ImageManager(new ImageGdDriver());
    $stillExisting = $manager->read($existingPath);
    $pixel = $stillExisting->pickColor(600, 300)->toArray();
    expect($pixel[2])->toBeGreaterThan($pixel[0]); // toujours la teinte bleue de l'existant

    cleanupOverwriteGuardTestFiles($slug);
});

test('une nouvelle capture valide remplace l\'existant, produit un master et reinitialise le focal (CA-3)', function () {
    $slug = 'test-guard-valid-capture';
    $tool = makeOverwriteGuardTestTool($slug);
    $tool->screenshot_focal_y = 300; // focal manuel pre-existant, doit etre efface par la nouvelle capture
    $tool->save();

    $existingPath = public_path("screenshots/{$slug}.jpg");
    writeUniformJpeg($existingPath, 1200, 630, [10, 10, 90]);

    // La vignette 1200x630 doit avoir EXACTEMENT ces dimensions (Brique 4, methode='screenshot') :
    // on ecrit directement une image 1200x630 valide non-uniforme, et un master a cote (brique 1).
    // $json est ici un callable : master_path n'est connu qu'une fois le vrai tempPath resolu par
    // Process::fake() (chemin temporaire genere dynamiquement par ScreenshotService::capture()).
    fakeCaptureProcess(
        fn (string $tempPath) => ['success' => true, 'method' => 'screenshot', 'size' => 40000, 'goto_status' => 'loaded', 'master_path' => $tempPath.'.master.jpg', 'final_url' => 'https://exemple-guard.test/', 'post_redirect_url' => 'https://exemple-guard.test/'],
        function (string $tempPath) {
            $img = imagecreatetruecolor(1200, 630);
            $palette = [[230, 25, 25], [25, 230, 25], [25, 25, 230], [230, 230, 25], [25, 230, 230], [230, 25, 230]];
            $band = (int) ceil(630 / count($palette));
            foreach ($palette as $i => [$r, $g, $b]) {
                $color = imagecolorallocate($img, $r, $g, $b);
                imagefilledrectangle($img, 0, $i * $band, 1199, min(($i + 1) * $band - 1, 629), $color);
            }
            imagejpeg($img, $tempPath, 90);
            imagedestroy($img);

            $masterPath = $tempPath.'.master.jpg';
            $master = imagecreatetruecolor(1200, 1400);
            $mc = imagecolorallocate($master, 100, 150, 200);
            imagefilledrectangle($master, 0, 0, 1199, 1399, $mc);
            imagejpeg($master, $masterPath, 90);
            imagedestroy($master);
        }
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeTrue();

    $manager = new ImageManager(new ImageGdDriver());
    $result = $manager->read($existingPath);
    expect($result->width())->toBe(1200);
    expect($result->height())->toBe(630);

    expect(File::exists(public_path("screenshots/{$slug}.jpg.bak")))->toBeTrue(); // backup pris avant remplacement
    expect(File::exists(public_path("screenshots/masters/{$slug}.jpg")))->toBeTrue(); // master deplace (brique 1)

    $tool->refresh();
    expect($tool->screenshot_focal_y)->toBe(0); // CA-3 : reinitialise malgre le focal manuel pre-existant

    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    cleanupOverwriteGuardTestFiles($slug);
});

test('screenshot_locked=true bloque toujours la (re)capture automatique (comportement existant inchange)', function () {
    $slug = 'test-guard-locked-blocks-auto';
    $tool = makeOverwriteGuardTestTool($slug);
    $tool->screenshot_locked = true;
    $tool->save();

    fakeCaptureProcess(['success' => true, 'method' => 'screenshot', 'size' => 40000, 'goto_status' => 'loaded']);

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();
    Process::assertNothingRan();

    cleanupOverwriteGuardTestFiles($slug);
});

test('CA-8 : un og:image de plus de 10 Mo est rejete avant tout decodage, fallback gradient prend le relais', function () {
    $slug = 'test-guard-ca8-filesize';
    $tool = makeOverwriteGuardTestTool($slug);

    fakeCaptureProcess(
        ['success' => true, 'method' => 'og:image', 'size' => 11 * 1024 * 1024, 'ogUrl' => 'https://exemple.test/og.jpg', 'goto_status' => 'blocked', 'final_url' => 'https://exemple-guard.test/', 'post_redirect_url' => 'https://exemple-guard.test/'],
        function (string $tempPath) {
            // Fichier > 10 Mo, volontairement invalide comme image (prouve que le rejet se fait
            // AVANT tout decodage - un vrai decodage sur ce contenu aurait leve une exception).
            file_put_contents($tempPath, str_repeat('x', 10 * 1024 * 1024 + 500));
        }
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();
    expect(File::exists(public_path("screenshots/{$slug}.jpg")))->toBeFalse();

    cleanupOverwriteGuardTestFiles($slug);
});

test('CA-8 : un og:image aux dimensions declarees > 8000px est rejete avant tout decodage', function () {
    $slug = 'test-guard-ca8-dimensions';
    $tool = makeOverwriteGuardTestTool($slug);

    fakeCaptureProcess(
        ['success' => true, 'method' => 'og:image', 'size' => 5000, 'ogUrl' => 'https://exemple.test/og.jpg', 'goto_status' => 'blocked', 'final_url' => 'https://exemple-guard.test/', 'post_redirect_url' => 'https://exemple-guard.test/'],
        function (string $tempPath) {
            // Image reelle mais extremement large (> 8000px sur un cote) - tres compressible donc
            // petite en octets, pour isoler specifiquement la garde par DIMENSIONS de la garde par
            // TAILLE DE FICHIER (deux criteres distincts de la meme garde anti-bombe, CA-8).
            $img = imagecreatetruecolor(8100, 50);
            $color = imagecolorallocate($img, 200, 200, 200);
            imagefilledrectangle($img, 0, 0, 8099, 49, $color);
            imagejpeg($img, $tempPath, 80);
            imagedestroy($img);
        }
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();
    expect(File::exists(public_path("screenshots/{$slug}.jpg")))->toBeFalse();

    cleanupOverwriteGuardTestFiles($slug);
});

test('CA-6 : un fallback og:image hors ratio [1.2, 3.0] est compose en contain + fond floute, sans jamais couper le sujet', function () {
    $slug = 'test-guard-ca6-contain';
    $tool = makeOverwriteGuardTestTool($slug);

    $sourcePath = sys_get_temp_dir().'/og-source-'.$slug.'.jpg';
    writeBandedMarkerJpeg($sourcePath, 800); // ratio 1.0, hors [1.2, 3.0]

    fakeCaptureProcess(
        ['success' => true, 'method' => 'og:image', 'size' => filesize($sourcePath), 'ogUrl' => 'https://exemple.test/og.jpg', 'goto_status' => 'blocked', 'final_url' => 'https://exemple-guard.test/', 'post_redirect_url' => 'https://exemple-guard.test/'],
        fn (string $tempPath) => copy($sourcePath, $tempPath)
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeTrue();

    $manager = new ImageManager(new ImageGdDriver());
    $result = $manager->read(public_path("screenshots/{$slug}.jpg"));
    expect($result->width())->toBe(1200);
    expect($result->height())->toBe(630);

    // Les deux marqueurs extremes du sujet (noir en haut, rouge en bas de la source) doivent
    // toujours etre presents quelque part dans le canevas final - preuve qu'aucun cover() n'a
    // coupe le sujet (un cover() naif aurait tres probablement perdu l'un des deux).
    $hasDark = scanForColorMatch($result, fn ($r, $g, $b) => $r < 60 && $g < 60 && $b < 60);
    $hasRed = scanForColorMatch($result, fn ($r, $g, $b) => $r > 150 && $g < 90 && $b < 90);

    expect($hasDark)->toBeTrue();
    expect($hasRed)->toBeTrue();

    @unlink($sourcePath);
    cleanupOverwriteGuardTestFiles($slug);
});

test('CA-7 (sanity locale) : un ratio dans [1.2, 3.0] continue d\'utiliser cover(1200,630), comportement identique a aujourd\'hui', function () {
    $slug = 'test-guard-ratio-cover';
    $tool = makeOverwriteGuardTestTool($slug);

    $sourcePath = sys_get_temp_dir().'/og-source-cover-'.$slug.'.jpg';
    writeBandedMarkerJpeg($sourcePath, 700); // on va l'etirer en 1400x700 (ratio 2.0) via une copie redimensionnee
    // Reconstruit une source 1400x700 (ratio 2.0, dans la plage [1.2,3.0]) a partir des bandes.
    $manager = new ImageManager(new ImageGdDriver());
    $wide = $manager->read($sourcePath)->resize(1400, 700);
    $wide->toJpeg(90)->save($sourcePath);

    fakeCaptureProcess(
        ['success' => true, 'method' => 'og:image', 'size' => filesize($sourcePath), 'ogUrl' => 'https://exemple.test/og.jpg', 'goto_status' => 'blocked', 'final_url' => 'https://exemple-guard.test/', 'post_redirect_url' => 'https://exemple-guard.test/'],
        fn (string $tempPath) => copy($sourcePath, $tempPath)
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeTrue();

    $result = $manager->read(public_path("screenshots/{$slug}.jpg"));
    expect($result->width())->toBe(1200);
    expect($result->height())->toBe(630);

    @unlink($sourcePath);
    cleanupOverwriteGuardTestFiles($slug);
});

/**
 * Garde-fou navigation (2026-08-30) - couvre le pilote de recaptures qui a produit 2 faux succes
 * sur 10 : une capture de pleine hauteur, valide en apparence, montrant la MAUVAISE page (bandeau
 * cookies dont la cascade de rejet avait clique un lien de navigation reel). Preuve la plus forte
 * (rejeu contre cursor.com et surferseo.com, les deux sites reels du pilote) documentee dans le
 * rapport de livraison - ces tests-ci couvrent la logique du garde-fou lui-meme, deterministe et
 * sans reseau, dans le meme esprit que le reste de ce fichier (Process::fake()).
 */
test('URL finale sur un domaine totalement different (derive de navigation) refuse le nouveau maitre, conserve l\'existant, et journalise', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_screenshots')->andReturnSelf();

    $slug = 'test-guard-nav-cross-domain';
    $tool = makeOverwriteGuardTestTool($slug);
    $existingPath = public_path("screenshots/{$slug}.jpg");
    writeUniformJpeg($existingPath, 1200, 630, [90, 10, 10]); // existant distinctif (rouge fonce)

    fakeCaptureProcess(
        // La cascade de rejet des bandeaux a navigue vers un widget tiers hors du domaine attendu -
        // contenu par ailleurs parfaitement valide (non-uniforme, bonnes dimensions), donc SEUL ce
        // garde-fou peut l'intercepter.
        ['success' => true, 'method' => 'screenshot', 'size' => 40000, 'goto_status' => 'loaded', 'final_url' => 'https://widget-tiers.example/help', 'post_redirect_url' => 'https://exemple-guard.test/'],
        fn (string $tempPath) => writeBanded1200x630Jpeg($tempPath)
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();

    $manager = new ImageManager(new ImageGdDriver());
    $stillExisting = $manager->read($existingPath);
    $pixel = $stillExisting->pickColor(600, 300)->toArray();
    expect($pixel[0])->toBeGreaterThan($pixel[1]); // toujours la teinte rouge de l'existant, jamais remplacee

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message) => is_string($message)
            && str_contains($message, 'rejet - URL finale hors domaine attendu')
            && str_contains($message, 'widget-tiers.example'));

    cleanupOverwriteGuardTestFiles($slug);
});

test('un lien de redirection dont l\'URL demandee et le domaine final different est accepte (230 fiches)', function () {
    $slug = 'test-guard-nav-redirect-link';
    $tool = makeOverwriteGuardTestTool($slug);
    $tool->url = 'https://www.producthunt.com/r/p/333330'; // lien de suivi, jamais le domaine reel
    $tool->save();

    fakeCaptureProcess(
        // Le navigateur a suivi la redirection AVANT toute interaction (post_redirect_url) puis y
        // est reste jusqu'a la capture (final_url identique) - reproduit la redirection reelle
        // notion.so -> notion.com mesuree le 2026-08-30 en rejouant le script corrige contre le
        // vrai site.
        fn (string $tempPath) => ['success' => true, 'method' => 'screenshot', 'size' => 40000, 'goto_status' => 'loaded', 'master_path' => $tempPath.'.master.jpg', 'post_redirect_url' => 'https://www.realproduct.example/', 'final_url' => 'https://www.realproduct.example/'],
        function (string $tempPath) { writeBanded1200x630Jpeg($tempPath); }
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeTrue();

    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    cleanupOverwriteGuardTestFiles($slug);
});

test('un sous-domaine different (www/apex, pays) est tolere, sans post_redirect_url fourni', function () {
    $slug = 'test-guard-nav-subdomain';
    $tool = makeOverwriteGuardTestTool($slug); // tool->url = https://exemple-guard.test

    fakeCaptureProcess(
        fn (string $tempPath) => ['success' => true, 'method' => 'screenshot', 'size' => 40000, 'goto_status' => 'loaded', 'master_path' => $tempPath.'.master.jpg', 'final_url' => 'https://fr.exemple-guard.test/'],
        function (string $tempPath) { writeBanded1200x630Jpeg($tempPath); }
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeTrue();

    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    cleanupOverwriteGuardTestFiles($slug);
});

test('final_url absente du JSON est refusee par prudence (fail-closed), et journalisee', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_screenshots')->andReturnSelf();

    $slug = 'test-guard-nav-missing-final-url';
    $tool = makeOverwriteGuardTestTool($slug);
    $existingPath = public_path("screenshots/{$slug}.jpg");
    writeUniformJpeg($existingPath, 1200, 630, [10, 90, 10]); // existant distinctif (vert fonce)

    fakeCaptureProcess(
        // Script non instrumente (ancienne version) ou echec de lecture cote Node : le champ
        // final_url manque totalement - jamais assimile a un succes silencieux.
        ['success' => true, 'method' => 'screenshot', 'size' => 40000, 'goto_status' => 'loaded'],
        fn (string $tempPath) => writeBanded1200x630Jpeg($tempPath)
    );

    $service = new ScreenshotService();
    $ok = $service->capture($tool);

    expect($ok)->toBeFalse();

    $manager = new ImageManager(new ImageGdDriver());
    $stillExisting = $manager->read($existingPath);
    $pixel = $stillExisting->pickColor(600, 300)->toArray();
    expect($pixel[1])->toBeGreaterThan($pixel[0]); // toujours la teinte verte de l'existant

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message) => is_string($message) && str_contains($message, 'rejet - URL finale hors domaine attendu'));

    cleanupOverwriteGuardTestFiles($slug);
});
