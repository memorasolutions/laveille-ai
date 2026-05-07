<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain; charset=utf-8');

$tools = Tool::where('status', '!=', 'draft')->orderBy('id')->get(['id', 'slug', 'url', 'screenshot', 'name']);
$nullDb = []; $missingFile = []; $gradientOnly = []; $smallReal = []; $okReal = [];
foreach ($tools as $t) {
    $slug = $t->getTranslation('slug', 'fr_CA');
    if (! $slug) continue;
    if (! $t->screenshot) { $nullDb[] = $slug; continue; }
    $abs = public_path($t->screenshot);
    if (! File::exists($abs)) { $missingFile[] = $slug; continue; }
    $sz = File::size($abs);
    if ($sz < 5000) $missingFile[] = "$slug ({$sz}b)";
    elseif ($sz < 35000) $gradientOnly[] = "$slug ({$sz}b)";
    elseif ($sz < 50000) $smallReal[] = "$slug ({$sz}b)";
    else $okReal[] = "$slug ({$sz}b)";
}

echo "=== AUDIT FINAL ===\n";
echo "Total: " . count($tools) . "\n";
echo "DB screenshot NULL: " . count($nullDb) . "\n";
echo "Fichier absent: " . count($missingFile) . "\n";
echo "Gradient (5-35KB): " . count($gradientOnly) . "\n";
echo "Petite capture (35-50KB): " . count($smallReal) . "\n";
echo "Vraie capture (>= 50KB): " . count($okReal) . "\n\n";

echo "=== SANS MINIATURE (NULL DB ou fichier absent) ===\n";
foreach (array_merge($nullDb, $missingFile) as $s) echo "  - $s\n";

echo "\n=== AVEC GRADIENT (couvert visuellement, pas vraie capture) ===\n";
foreach (array_slice($gradientOnly, 0, 30) as $s) echo "  - $s\n";
if (count($gradientOnly) > 30) echo "  ... + " . (count($gradientOnly) - 30) . " autres\n";

@unlink(__FILE__);
