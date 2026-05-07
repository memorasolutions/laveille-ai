<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

header('Content-Type: text/plain; charset=utf-8');

// Cible : outils avec gradient fallback (entre 20-35 KB) → à remplacer par vraie capture Puppeteer
// PLUS outils sans screenshot du tout (mais on a déjà fait gradient pour eux)
// Filter : url non null + screenshot existant entre 5-35 KB (typique gradient)
$tools = Tool::whereNotNull('url')
    ->where('status', '!=', 'draft')
    ->whereNotNull('screenshot')
    ->orderBy('id')
    ->get(['id', 'slug', 'url', 'screenshot']);

$candidates = [];
foreach ($tools as $t) {
    $slug = $t->getTranslation('slug', 'fr_CA');
    if (! $slug || ! $t->url) continue;
    $abs = public_path($t->screenshot);
    if (! File::exists($abs)) {
        $candidates[] = ['slug' => $slug, 'url' => $t->url, 'size' => 0];
        continue;
    }
    $sz = File::size($abs);
    // 5-35 KB = probablement gradient fallback (vraies captures Puppeteer >= 50 KB généralement)
    if ($sz <= 35000) {
        $candidates[] = ['slug' => $slug, 'url' => $t->url, 'size' => $sz];
    }
}

echo "TOTAL candidates (gradient/missing): " . count($candidates) . "\n";
echo "Format TSV : slug\\turl\\tsize\n";
foreach ($candidates as $c) {
    echo $c['slug'] . "\t" . $c['url'] . "\t" . $c['size'] . "\n";
}
@unlink(__FILE__);
