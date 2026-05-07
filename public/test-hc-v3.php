<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

set_time_limit(300);
ini_set('memory_limit', '256M');
header('Content-Type: text/plain; charset=utf-8');

$start = microtime(true);
$exitCode = $kernel->call('directory:health-check-report', [
    '--limit' => 10,
    '--no-email' => true,
]);
$elapsed = round(microtime(true) - $start, 2);
$out = $kernel->output();

echo "Elapsed: {$elapsed}s\n";
echo "Exit: $exitCode\n";
echo "=== OUTPUT ===\n";
echo $out;

// Garde le script (pas auto-delete) pour debug
