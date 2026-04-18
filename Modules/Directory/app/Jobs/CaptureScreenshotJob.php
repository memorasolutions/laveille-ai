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

    public int $timeout = 400;

    public int $tries = 1;

    public function __construct(public Tool $tool) {}

    public function handle(ScreenshotService $service): void
    {
        if (! ScreenshotService::isAvailable()) {
            Log::warning("[CaptureScreenshotJob] Service de capture indisponible pour Tool #{$this->tool->id}.");

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
