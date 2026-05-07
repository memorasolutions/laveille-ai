<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\File;
header('Content-Type: text/plain');
$dir = public_path('screenshots');
$files = glob("$dir/*.jpg");
$buckets = ['gradient (5-35KB)' => 0, 'small_capture (35-50KB)' => 0, 'capture (50-150KB)' => 0, 'large_capture (>150KB)' => 0];
foreach ($files as $f) {
    $sz = filesize($f);
    if ($sz < 35000) $buckets['gradient (5-35KB)']++;
    elseif ($sz < 50000) $buckets['small_capture (35-50KB)']++;
    elseif ($sz < 150000) $buckets['capture (50-150KB)']++;
    else $buckets['large_capture (>150KB)']++;
}
echo "TOTAL screenshots prod: " . count($files) . "\n";
foreach ($buckets as $k => $n) echo "  $k: $n\n";
@unlink(__FILE__);
