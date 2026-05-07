<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

header('Content-Type: text/plain; charset=utf-8');

// Etape 1 : audit — count des cas
$total = Tool::count();
$nullScreenshot = Tool::whereNull('screenshot')->count();
$emptyScreenshot = Tool::whereNotNull('screenshot')->where('screenshot', '')->count();
$tools = Tool::whereNotNull('screenshot')->where('screenshot', '!=', '')->get(['id', 'screenshot', 'slug']);
$missingFile = 0;
$validFile = 0;
foreach ($tools as $t) {
    $abs = public_path($t->screenshot);
    if (! File::exists($abs)) $missingFile++;
    else $validFile++;
}

echo "=== AUDIT directory_tools screenshots ===\n";
echo "Total outils: $total\n";
echo "Champ screenshot NULL: $nullScreenshot\n";
echo "Champ screenshot vide '': $emptyScreenshot\n";
echo "Champ rempli mais fichier absent: $missingFile\n";
echo "Champ rempli + fichier OK: $validFile\n";
echo "TOTAL à traiter: " . ($nullScreenshot + $emptyScreenshot + $missingFile) . "\n\n";

// Sample : 5 sans screenshot
echo "=== Sample 5 sans screenshot ===\n";
$samples = Tool::whereNull('screenshot')->orWhere('screenshot', '')->take(5)->get(['id', 'name', 'slug']);
foreach ($samples as $s) {
    echo "  id={$s->id} slug=" . ($s->getTranslation('slug', 'fr_CA') ?? '?') . "\n";
}

@unlink(__FILE__);
