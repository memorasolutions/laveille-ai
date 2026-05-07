<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

set_time_limit(900);
ini_set('memory_limit', '512M');
header('Content-Type: text/plain');

// Reset lock pour pouvoir relancer (test)
@unlink(storage_path('app/health-check-report.lock'));
$kernel->call('cache:clear');
$kernel->call('config:clear');
echo "Cache cleared, lock released.\n\n";

$start = microtime(true);
$exitCode = $kernel->call('directory:health-check-report', ['--no-email' => true]);
$elapsed = round(microtime(true) - $start, 1);

echo "Elapsed: {$elapsed}s | Exit: $exitCode\n";
echo "=== OUTPUT ===\n";
echo $kernel->output();
@unlink(__FILE__);
