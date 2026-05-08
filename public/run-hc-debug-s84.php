<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
set_time_limit(900);
ini_set('memory_limit', '512M');
header('Content-Type: text/plain');

echo "Started: " . date('H:i:s') . "\n";
echo "Admin: " . (config('app.superadmin_email') ?: 'UNDEFINED') . "\n";
echo "Lock file: " . (file_exists(storage_path('app/health-check-report.lock')) ? 'EXISTS' : 'ABSENT') . "\n\n";
flush();

// Reset lock pour 1 envoi
@unlink(storage_path('app/health-check-report.lock'));
$kernel->call('cache:clear');
$kernel->call('config:clear');

try {
    $start = microtime(true);
    echo "Calling command...\n";
    flush();
    $exitCode = $kernel->call('directory:health-check-report');
    $elapsed = round(microtime(true) - $start, 1);
    echo "\nElapsed: {$elapsed}s | Exit: $exitCode\n=== OUTPUT ===\n";
    echo $kernel->output();
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "\nLock après: " . (file_exists(storage_path('app/health-check-report.lock')) ? 'EXISTS' : 'ABSENT') . "\n";
// Pas d'auto-delete pour debug
