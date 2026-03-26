<?php

declare(strict_types=1);

namespace Modules\Directory\Observers;

use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

class ToolObserver
{
    public function saved(Tool $tool): void
    {
        if (
            $tool->wasChanged('status')
            && $tool->status === 'published'
            && (empty($tool->screenshot) || str_starts_with((string) $tool->screenshot, 'http'))
            && ScreenshotService::isAvailable()
        ) {
            (new ScreenshotService())->captureWithRetry($tool);
        }
    }
}
