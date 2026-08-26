<?php

namespace Modules\Directory\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

class CaptureScreenshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Aligne sur le --timeout des workers (330), qui prime de toute facon sur cette propriete.
    //
    // 330 et non 270 : mesure du 2026-08-26 apres une RECIDIVE. captureWithRetry fait 3 tentatives
    // de 90 s, mais ATTEND aussi entre elles (sleep(2^n) : 2 s puis 4 s). La duree maximale reelle
    // est donc 3x90 + 6 = 276 s, pas 270. Un worker a 270 tuait le job 6 secondes avant la fin de
    // sa derniere tentative - avant meme de compter le demarrage de Node. 330 laisse une vraie marge.
    public int $timeout = 330;

    public int $tries = 1;

    public function __construct(public Tool $tool)
    {
        $this->onQueue('screenshots');
    }

    public function handle(ScreenshotService $service): void
    {
        if (! ScreenshotService::isAvailable()) {
            Log::channel('directory_screenshots')->warning("[CaptureScreenshotJob] Service de capture indisponible pour Tool #{$this->tool->id}.");

            return;
        }

        $result = $service->captureWithRetry($this->tool);

        if ($result) {
            Log::info("[CaptureScreenshotJob] Screenshot capturé avec succès pour Tool #{$this->tool->id}.");
        } else {
            Log::error("[CaptureScreenshotJob] Échec de la capture après 3 tentatives pour Tool #{$this->tool->id}.");
        }
    }
}
