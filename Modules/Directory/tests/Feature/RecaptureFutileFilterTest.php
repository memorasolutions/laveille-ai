<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2087 lot 1 (2026-09-03) - couvre le filtre anti-"recaptures futiles" de
 * directory:dispatch-margin-recapture. Mesure d'origine : sur 100 jobs de la queue 'screenshots',
 * 48 echecs francs et 48 "succes" par repli og:image qui NE PRODUIT PAS de master
 * (ScreenshotService::capture(), le chemin og:image n'ecrit jamais dans screenshots/masters/) -
 * ces outils redeviennent candidats au dispatch suivant sans jamais progresser (3 masters sur
 * 100). Ce fichier verifie que la commande ECARTE temporairement ces candidats plutot que de les
 * remettre en file sans progres, de facon transparente (compteur futile_skipped, jamais un cap
 * silencieux) et reversible (--include-futile, --futile-grace-days).
 *
 * Noms de fonctions PROPRES a ce fichier (prefixe rff*) - jamais ceux de
 * DispatchMarginRecaptureCommandTest.php : les deux fichiers sont charges dans le meme processus
 * PHP quand la suite complete du module s'execute, une meme signature de fonction dans les deux
 * causerait une erreur fatale de redeclaration.
 *
 * La reversibilite de la migration (2026_09_03_100000_add_screenshot_attempt_tracking_to_directory_tools)
 * n'est pas testee explicitement ici : RefreshDatabase l'exerce deja implicitement a chaque
 * execution de la suite (migrate frais avant chaque run).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr_CA']);
    config(['services.cloudflare.zone_id' => null, 'services.cloudflare.api_token' => null]);
});

/**
 * Construction directe (pas de ToolFactory dans ce module - meme convention que
 * TutorialRelevanceGuardTest.php et DispatchMarginRecaptureCommandTest.php). Pose par defaut les
 * attributs minimaux pour que l'outil soit un candidat "recapture reseau" une fois l'image
 * source posee par rffMakeSourceImage() : published, url, screenshot pointant vers le fichier de
 * test (la requete de la commande filtre sur whereNotNull('screenshot')).
 */
function rffMakeTool(string $slug, array $attributs = []): Tool
{
    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Futile '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-futile-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->screenshot = "screenshots/{$slug}.jpg";

    foreach ($attributs as $cle => $valeur) {
        $tool->{$cle} = $valeur;
    }

    $tool->save();
    $tool->refresh();

    return $tool;
}

/**
 * Image JPEG reelle 400x200 (bruit pseudo-aleatoire, jamais une teinte unie - une compression
 * JPEG d'une couleur plate tombe sous le seuil de 1000 octets exige par la commande pour
 * considerer la vignette exploitable). Mise a l'echelle a THUMB_WIDTH (1200) cette source donne
 * 1200x600 <= THUMB_HEIGHT (630) : classify() = STATUS_TOO_SMALL, et sans master existant la
 * commande tombe dans la branche "recapture reseau" - le seul chemin ou le filtre anti-futiles
 * s'applique. Meme fixture que la fiche "trop courte" de DispatchMarginRecaptureCommandTest.php.
 */
function rffMakeSourceImage(string $absolutePath): void
{
    $dir = dirname($absolutePath);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $img = imagecreatetruecolor(400, 200);
    $cell = 8;
    for ($y = 0; $y < 200; $y += $cell) {
        for ($x = 0; $x < 400; $x += $cell) {
            $color = imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imagefilledrectangle($img, $x, $y, min($x + $cell - 1, 399), min($y + $cell - 1, 199), $color);
        }
    }
    imagejpeg($img, $absolutePath, 90);
    imagedestroy($img);
}

function rffCleanup(string $slug): void
{
    @unlink(public_path("screenshots/masters/{$slug}.jpg"));
    @unlink(public_path("screenshots/{$slug}.jpg"));
}

it('exclut du dispatch un outil dont la derniere tentative og_image date d\'hier, et le compte en futile_skipped', function () {
    $slug = 'futile-og-hier-'.uniqid();
    rffMakeTool($slug, [
        'screenshot_last_attempt_result' => 'og_image',
        'screenshot_last_attempt_at' => now()->subDay(),
    ]);
    rffMakeSourceImage(public_path("screenshots/{$slug}.jpg"));

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture', ['--dry-run' => true, '--restart-every' => 0])
        ->expectsOutputToContain('écartés comme futiles 1.')
        ->expectsOutputToContain('0 recapture(s) réseau')
        ->assertExitCode(0);

    rffCleanup($slug);
});

it('reinclut le meme outil quand --include-futile desactive completement le filtre', function () {
    $slug = 'futile-include-'.uniqid();
    rffMakeTool($slug, [
        'screenshot_last_attempt_result' => 'og_image',
        'screenshot_last_attempt_at' => now()->subDay(),
    ]);
    rffMakeSourceImage(public_path("screenshots/{$slug}.jpg"));

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture', ['--dry-run' => true, '--restart-every' => 0, '--include-futile' => true])
        ->expectsOutputToContain('écartés comme futiles 0.')
        ->expectsOutputToContain('1 recapture(s) réseau')
        ->assertExitCode(0);

    rffCleanup($slug);
});

it('reinclut une tentative og_image vieille de 45 jours quand la grace est de 30 jours', function () {
    $slug = 'futile-grace-expiree-'.uniqid();
    rffMakeTool($slug, [
        'screenshot_last_attempt_result' => 'og_image',
        'screenshot_last_attempt_at' => now()->subDays(45),
    ]);
    rffMakeSourceImage(public_path("screenshots/{$slug}.jpg"));

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture', ['--dry-run' => true, '--restart-every' => 0, '--futile-grace-days' => 30])
        ->expectsOutputToContain('écartés comme futiles 0.')
        ->expectsOutputToContain('1 recapture(s) réseau')
        ->assertExitCode(0);

    rffCleanup($slug);
});

it('n\'exclut jamais un outil dont le dernier resultat est screenshot, meme tres recent', function () {
    $slug = 'futile-screenshot-recent-'.uniqid();
    rffMakeTool($slug, [
        'screenshot_last_attempt_result' => 'screenshot',
        'screenshot_last_attempt_at' => now()->subHour(),
    ]);
    rffMakeSourceImage(public_path("screenshots/{$slug}.jpg"));

    Queue::fake();
    $this->artisan('directory:dispatch-margin-recapture', ['--dry-run' => true, '--restart-every' => 0])
        ->expectsOutputToContain('écartés comme futiles 0.')
        ->expectsOutputToContain('1 recapture(s) réseau')
        ->assertExitCode(0);

    rffCleanup($slug);
});
