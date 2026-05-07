<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
set_time_limit(900);
ini_set('memory_limit', '512M');
header('Content-Type: text/plain');

// Reset lock pour test
@unlink(storage_path('app/health-check-report.lock'));
$kernel->call('cache:clear');
$kernel->call('config:clear');

$start = microtime(true);
echo "=== Health-check FULL avec ENVOI EMAIL (test auto user) ===\n";
echo "Started: " . date('H:i:s') . "\n";
echo "Admin: " . (config('app.superadmin_email') ?: 'UNDEFINED') . "\n\n";

$exitCode = $kernel->call('directory:health-check-report');

$elapsed = round(microtime(true) - $start, 1);
echo "\nElapsed: {$elapsed}s | Exit: $exitCode\n=== OUTPUT ===\n";
echo $kernel->output();
@unlink(__FILE__);
