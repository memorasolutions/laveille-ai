<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

set_time_limit(600); // 10 min max
header('Content-Type: text/plain; charset=utf-8');
echo "=== Batch screenshot fallback gradient ===\n";

// Step 1 : Fix URL Qwen (user signal)
$qwen = Tool::where('slug->fr_CA', 'qwen')->orWhereJsonContains('name->fr_CA', 'Qwen')->first();
if ($qwen) {
    $qwen->url = 'https://qwen.ai/home';
    $qwen->save();
    echo "FIXED Qwen URL → https://qwen.ai/home (id={$qwen->id})\n\n";
}

// Step 2 : batch gradient fallback pour outils sans screenshot
$tools = Tool::whereNull('screenshot')->get(['id', 'name', 'slug']);
$total = count($tools);
echo "À traiter: $total outils\n\n";

$created = 0; $skipped = 0; $errors = 0;
$start = microtime(true);
foreach ($tools as $i => $tool) {
    try {
        $slug = $tool->getTranslation('slug', 'fr_CA');
        if (empty($slug)) { $skipped++; continue; }
        $path = public_path("screenshots/{$slug}.jpg");
        // Skip si existe déjà ≥ 5KB (anti-overwrite garde-fou bd510bd6)
        if (File::exists($path) && File::size($path) >= 5000) {
            // Update DB pour pointer vers le fichier qui existe
            $tool->screenshot = "screenshots/{$slug}.jpg";
            $tool->saveQuietly();
            $skipped++;
            continue;
        }
        $ok = ScreenshotService::generateFallbackGradient($tool);
        if ($ok) $created++; else $errors++;
        if (($i + 1) % 50 === 0) {
            $elapsed = round(microtime(true) - $start, 1);
            echo "  [{$elapsed}s] Progress {$i}/{$total} (created=$created, skipped=$skipped, errors=$errors)\n";
            flush();
        }
    } catch (\Throwable $e) {
        $errors++;
        if ($errors <= 5) echo "  ERROR id={$tool->id}: {$e->getMessage()}\n";
    }
}
$elapsed = round(microtime(true) - $start, 1);
echo "\n=== RÉSUMÉ ({$elapsed}s) ===\n";
echo "Created: $created\nSkipped: $skipped\nErrors: $errors\nTotal: $total\n";

@unlink(__FILE__);
echo "AUTO-DELETE OK\n";
