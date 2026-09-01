<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2086 (2026-09-01) - couvre les trois chemins d'execution de
 * CaptureScreenshotJob::handle(), le dernier maillon du meme genre de bogue deja corrige dans
 * ScreenshotService.php par les tickets #2086 (v1.242.9) et #2088 (v1.242.10) : un Log:: sur le
 * canal par defaut, avale par LOG_LEVEL=error en production. Le chemin succes (niveau info) y
 * restait totalement invisible ; le chemin echec (niveau error, qui passe le filtre par defaut)
 * apparaissait deja, mais sur le mauvais canal (storage/logs/laravel.log au lieu de
 * directory_screenshots.log). Les trois chemins journalisent desormais sur le canal dedie, comme
 * le fait deja ScreenshotService.php dans son ensemble.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeCaptureJobLoggingTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Job Logging '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-job-logging-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

it('journalise le succes d\'une capture sur le canal dedie directory_screenshots, jamais le canal par defaut', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_screenshots')->andReturnSelf();

    // isAvailable() lit services.browsershot.node_path : artisan sert de fichier existant sur
    // tout environnement (meme convention que ScreenshotOverwriteGuardTest.php).
    config(['services.browsershot.node_path' => base_path('artisan')]);

    $tool = makeCaptureJobLoggingTestTool('job-logging-succes-'.uniqid());

    $this->mock(ScreenshotService::class, function ($mock) {
        $mock->shouldReceive('captureWithRetry')->once()->andReturn(true);
    });

    (new CaptureScreenshotJob($tool))->handle(app(ScreenshotService::class));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn ($message) => is_string($message)
            && str_contains($message, '[CaptureScreenshotJob]')
            && str_contains($message, 'capturé avec succès')
            && str_contains($message, "Tool #{$tool->id}"));
});

it('journalise l\'echec d\'une capture apres 3 tentatives sur le canal dedie, jamais le canal par defaut', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_screenshots')->andReturnSelf();

    config(['services.browsershot.node_path' => base_path('artisan')]);

    $tool = makeCaptureJobLoggingTestTool('job-logging-echec-'.uniqid());

    $this->mock(ScreenshotService::class, function ($mock) {
        $mock->shouldReceive('captureWithRetry')->once()->andReturn(false);
    });

    (new CaptureScreenshotJob($tool))->handle(app(ScreenshotService::class));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn ($message) => is_string($message)
            && str_contains($message, '[CaptureScreenshotJob]')
            && str_contains($message, 'Échec de la capture')
            && str_contains($message, "Tool #{$tool->id}"));
});

it('journalise deja correctement le service indisponible sur le canal dedie (non regression)', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('directory_screenshots')->andReturnSelf();

    // Chemin sans fichier Node exploitable : isAvailable() renvoie false, captureWithRetry()
    // n'est jamais appelee.
    config(['services.browsershot.node_path' => '/chemin-inexistant-'.uniqid().'/node']);

    $tool = makeCaptureJobLoggingTestTool('job-logging-indisponible-'.uniqid());

    $this->mock(ScreenshotService::class, function ($mock) {
        $mock->shouldNotReceive('captureWithRetry');
    });

    (new CaptureScreenshotJob($tool))->handle(app(ScreenshotService::class));

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message) => is_string($message)
            && str_contains($message, '[CaptureScreenshotJob]')
            && str_contains($message, 'Service de capture indisponible')
            && str_contains($message, "Tool #{$tool->id}"));
});
