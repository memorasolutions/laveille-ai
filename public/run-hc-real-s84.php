<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
set_time_limit(900);
ini_set('memory_limit', '512M');
header('Content-Type: text/plain');

// Auto-delete EN PREMIER pour empêcher tout 2e appel
@unlink(__FILE__);

// Reset lock pour permettre 1 envoi de test
@unlink(storage_path('app/health-check-report.lock'));
$kernel->call('cache:clear');
$kernel->call('config:clear');

$start = microtime(true);
echo "Started: " . date('H:i:s') . " — admin: " . (config('app.superadmin_email') ?: 'UNDEFINED') . "\n\n";

$exitCode = $kernel->call('directory:health-check-report');

$elapsed = round(microtime(true) - $start, 1);
echo "\nElapsed: {$elapsed}s | Exit: $exitCode\n=== OUTPUT ===\n";
echo $kernel->output();
