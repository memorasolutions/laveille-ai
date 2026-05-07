<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;
use Illuminate\Support\Facades\File;

header('Content-Type: text/plain');
$tool = Tool::where('slug->fr_CA', 'datagrout-ai')->first();
if (! $tool) { echo "TOOL NOT FOUND"; exit; }

// Force regen : delete corrupted file first
$path = public_path('screenshots/datagrout-ai.jpg');
if (File::exists($path)) {
    $oldSize = File::size($path);
    if ($oldSize < 5000) {
        unlink($path);
        echo "Deleted corrupt file (was $oldSize bytes)\n";
    }
}

// Generate fresh gradient
$ok = ScreenshotService::generateFallbackGradient($tool);
echo "Regen result: " . ($ok ? 'OK' : 'FAIL') . "\n";
if (File::exists($path)) {
    echo "New size: " . File::size($path) . " bytes\n";
}
@unlink(__FILE__);
