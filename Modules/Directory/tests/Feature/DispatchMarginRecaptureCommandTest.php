<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif #2087 (2026-08-31) - couvre directory:dispatch-margin-recapture, la commande qui
 * identifie les outils publiés dont la vignette n'offre aucune marge de recadrage
 * (ScreenshotFocalService::deriveThumbnail ne peut jamais déplacer le point focal quand le master
 * ne dépasse pas THUMB_HEIGHT). Elle ne doit JAMAIS exécuter de capture réseau elle-même : le seul
 * effet attendu côté recapture est une mise en file (CaptureScreenshotJob sur la queue
 * 'screenshots'), jamais un appel synchrone.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotFocalService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr_CA']);
    config(['services.cloudflare.zone_id' => null, 'services.cloudflare.api_token' => null]);
});

/** Construction directe (pas de ToolFactory dans ce module - même convention que les tests voisins). */
function makeMarginDispatchTestTool(string $slug, array $attributs = []): Tool
{
    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Marge '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-marge-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    // La requete de la commande filtre sur whereNotNull('screenshot') - sans cette colonne posee,
    // aucun outil de test n'est jamais trouve (meme convention de chemin que les fichiers crees
    // par makeMarginDispatchTestImage() dans chaque test).
    $tool->screenshot = "screenshots/{$slug}.jpg";

    foreach ($attributs as $cle => $valeur) {
        $tool->{$cle} = $valeur;
    }

    $tool->save();
    $tool->refresh();

    return $tool;
}

/**
 * Bruit pseudo-aléatoire (jamais une teinte unie) pour garantir un JPEG de plus de 1000 octets
 * même à petites dimensions - une compression JPEG d'une couleur plate tombe sous ce seuil et
 * ferait passer à tort la vignette de test pour « sans vignette locale exploitable ».
 */
function makeMarginDispatchTestImage(string $absolutePath, int $width, int $height): void
{
    $dir = dirname($absolutePath);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $img = imagecreatetruecolor($width, $height);
    $cell = 8;
    for ($y = 0; $y < $height; $y += $cell) {
        for ($x = 0; $x < $width; $x += $cell) {
            $color = imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imagefilledrectangle($img, $x, $y, min($x + $cell - 1, $width - 1), min($y + $cell - 1, $height - 1), $color);
        }
    }
    imagejpeg($img, $absolutePath, 90);
    imagedestroy($img);
}

function cleanupMarginDispatchTestFiles(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
}

/**
 * Source aux dimensions demesurees (correctif #2170) - remplissage uni (jamais le bruit
 * pseudo-aleatoire de makeMarginDispatchTestImage(), inutilement lent a cette echelle : ~375 Ko
 * meme unie a 6000x4000, tres au-dessus du plancher de 1000 octets). Le POIDS sur disque importe
 * peu ici - ce qui compte est que getimagesize() rapporte des dimensions dont le decodage
 * complet (largeur x hauteur x 4 octets) depasserait toute marge memoire raisonnable.
 */
function makeMarginDispatchOversizedTestImage(string $absolutePath): void
{
    $dir = dirname($absolutePath);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $img = imagecreatetruecolor(6000, 4000);
    imagefill($img, 0, 0, imagecolorallocate($img, 90, 110, 130));
    imagejpeg($img, $absolutePath, 90);
    imagedestroy($img);
}

it('laisse intact un outil dont le master existant depasse deja THUMB_HEIGHT (marge deja exploitable)', function () {
    $slug = 'marge-deja-ok-'.uniqid();
    $tool = makeMarginDispatchTestTool($slug);
    makeMarginDispatchTestImage(public_path("screenshots/{$slug}.jpg"), 1200, 900);
    makeMarginDispatchTestImage(public_path('screenshots/masters/'.$slug.'.jpg'), 1200, 900);
    $masterMTime = filemtime(public_path('screenshots/masters/'.$slug.'.jpg'));

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture')->assertExitCode(0);

    Queue::assertNotPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));
    expect(filemtime(public_path('screenshots/masters/'.$slug.'.jpg')))->toBe($masterMTime);

    cleanupMarginDispatchTestFiles($slug);
});

it('derive un master local gratuitement quand aucun master n\'existe mais que la vignette suffit deja', function () {
    $slug = 'marge-derivable-'.uniqid();
    $tool = makeMarginDispatchTestTool($slug);
    // 600x600 mis a l'echelle a THUMB_WIDTH (1200) donne 1200x1200 > THUMB_HEIGHT (630) : classify() = CREATED.
    makeMarginDispatchTestImage(public_path("screenshots/{$slug}.jpg"), 600, 600);
    expect(File::exists(public_path('screenshots/masters/'.$slug.'.jpg')))->toBeFalse();

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture')->assertExitCode(0);

    expect(File::exists(public_path('screenshots/masters/'.$slug.'.jpg')))->toBeTrue();
    $dimensions = getimagesize(public_path('screenshots/masters/'.$slug.'.jpg'));
    expect($dimensions[1])->toBeGreaterThan(ScreenshotFocalService::THUMB_HEIGHT);
    Queue::assertNotPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));

    cleanupMarginDispatchTestFiles($slug);
});

it('met en file une recapture reseau quand la vignette existante est structurellement trop courte', function () {
    $slug = 'marge-trop-courte-'.uniqid();
    $tool = makeMarginDispatchTestTool($slug);
    // 400x200 mis a l'echelle a 1200 de large donne 1200x600 <= THUMB_HEIGHT (630) : classify() = TOO_SMALL.
    makeMarginDispatchTestImage(public_path("screenshots/{$slug}.jpg"), 400, 200);

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture')->assertExitCode(0);

    Queue::assertPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));
    expect(File::exists(public_path('screenshots/masters/'.$slug.'.jpg')))->toBeFalse();

    cleanupMarginDispatchTestFiles($slug);
});

it('met en file une recapture reseau quand un master existant ne depasse pas THUMB_HEIGHT', function () {
    $slug = 'marge-master-court-'.uniqid();
    $tool = makeMarginDispatchTestTool($slug);
    makeMarginDispatchTestImage(public_path("screenshots/{$slug}.jpg"), 1200, 900);
    makeMarginDispatchTestImage(public_path('screenshots/masters/'.$slug.'.jpg'), 1200, 500);

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture')->assertExitCode(0);

    Queue::assertPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));

    cleanupMarginDispatchTestFiles($slug);
});

it('ne met JAMAIS en file un outil au screenshot verrouille - capture() le refuserait de toute facon', function () {
    $slug = 'marge-verrouille-'.uniqid();
    $tool = makeMarginDispatchTestTool($slug, ['screenshot_locked' => true]);
    makeMarginDispatchTestImage(public_path("screenshots/{$slug}.jpg"), 400, 200);

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture')->assertExitCode(0);

    Queue::assertNotPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));

    cleanupMarginDispatchTestFiles($slug);
});

it('dry-run ne cree aucun master et ne met rien en file', function () {
    $slugCreable = 'marge-dryrun-derivable-'.uniqid();
    $slugTropCourt = 'marge-dryrun-courte-'.uniqid();
    makeMarginDispatchTestTool($slugCreable);
    $toolTropCourt = makeMarginDispatchTestTool($slugTropCourt);
    makeMarginDispatchTestImage(public_path("screenshots/{$slugCreable}.jpg"), 600, 600);
    makeMarginDispatchTestImage(public_path("screenshots/{$slugTropCourt}.jpg"), 400, 200);

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture', ['--dry-run' => true])->assertExitCode(0);

    expect(File::exists(public_path('screenshots/masters/'.$slugCreable.'.jpg')))->toBeFalse();
    Queue::assertNotPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($toolTropCourt));

    cleanupMarginDispatchTestFiles($slugCreable);
    cleanupMarginDispatchTestFiles($slugTropCourt);
});

it('--limit borne le nombre de recaptures reseau mises en file, jamais les derivations locales gratuites', function () {
    $slugGratuit = 'marge-limite-gratuit-'.uniqid();
    $slugA = 'marge-limite-a-'.uniqid();
    $slugB = 'marge-limite-b-'.uniqid();
    // Cree EN PREMIER (id le plus bas, donc scanne avant l'arret sur limite - chunkById parcourt
    // par id croissant) : prouve que la derivation gratuite continue meme apres que la limite
    // reseau soit deja epuisee par un outil scanne plus tard, tant qu'elle est scannee avant.
    makeMarginDispatchTestTool($slugGratuit);
    makeMarginDispatchTestImage(public_path("screenshots/{$slugGratuit}.jpg"), 600, 600);
    makeMarginDispatchTestTool($slugA);
    makeMarginDispatchTestTool($slugB);
    makeMarginDispatchTestImage(public_path("screenshots/{$slugA}.jpg"), 400, 200);
    makeMarginDispatchTestImage(public_path("screenshots/{$slugB}.jpg"), 400, 200);

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture', ['--limit' => 1])->assertExitCode(0);

    Queue::assertPushed(CaptureScreenshotJob::class, 1);
    // La derivation locale gratuite n'est jamais bornee par la limite reseau.
    expect(File::exists(public_path('screenshots/masters/'.$slugGratuit.'.jpg')))->toBeTrue();

    cleanupMarginDispatchTestFiles($slugA);
    cleanupMarginDispatchTestFiles($slugB);
    cleanupMarginDispatchTestFiles($slugGratuit);
});

it('ne meurt jamais de memoire sur une source aux dimensions demesurees et met en file une recapture reseau - correctif #2170', function () {
    $slug = 'marge-trop-grande-'.uniqid();
    $tool = makeMarginDispatchTestTool($slug);
    // 6000x4000 : decoder cette source ENTIEREMENT en memoire (largeur x hauteur x 4 octets)
    // exigerait ~91 Mo, independamment de son poids en octets sur disque (~375 Ko unie). AVANT le
    // correctif #2170, classify() tentait quand meme ce decodage complet et faisait mourir tout
    // le PROCESSUS par un fatal PHP non rattrapable (meme signature d'erreur mesuree en
    // production - Allowed memory size ... exhausted - dans vendor/intervention/image/.../Gd/).
    makeMarginDispatchOversizedTestImage(public_path("screenshots/{$slug}.jpg"));

    // Marge memoire volontairement resserree AUTOUR de l'usage courant du processus de test
    // (jamais EN-DESSOUS, pour ne jamais faire planter la suite de tests elle-meme) - juste assez
    // pour rendre 6000x4000 sans equivoque hors budget, quel que soit le memory_limit reel du
    // runner (souvent illimite en local/CI, ce qui neutraliserait sinon silencieusement ce test).
    $originalMemoryLimit = ini_get('memory_limit');
    $tightLimitMb = (int) ceil(memory_get_usage(true) / 1048576) + 20;
    ini_set('memory_limit', $tightLimitMb.'M');

    try {
        Queue::fake();
        $this->artisan('directory:dispatch-margin-recapture')->assertExitCode(0);

        // STATUS_TOO_LARGE tombe dans la meme branche que STATUS_TOO_SMALL : aucun master local,
        // mais une recapture reseau (a la bonne taille) est mise en file - jamais "unreadable".
        Queue::assertPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));
        expect(File::exists(public_path('screenshots/masters/'.$slug.'.jpg')))->toBeFalse();
    } finally {
        ini_set('memory_limit', $originalMemoryLimit);
    }

    cleanupMarginDispatchTestFiles($slug);
});
