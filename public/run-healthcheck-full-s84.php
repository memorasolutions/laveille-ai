<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

set_time_limit(900); // 15 min max
ini_set('memory_limit', '512M');
header('Content-Type: text/plain; charset=utf-8');

$start = microtime(true);
echo "=== Health-check FULL run (envoi email actif) ===\n";
echo "Started: " . date('H:i:s') . "\n";
echo "Admin email: " . (config('app.superadmin_email') ?: config('app.admin_email') ?: env('ADMIN_EMAIL') ?: env('MAIL_FROM_ADDRESS') ?: 'UNDEFINED') . "\n\n";
flush();

$exitCode = $kernel->call('directory:health-check-report');

$elapsed = round(microtime(true) - $start, 1);
echo "\n=== TERMINÉ ===\n";
echo "Elapsed: {$elapsed}s\n";
echo "Exit code: $exitCode\n\n";
echo "=== ARTISAN OUTPUT ===\n";
echo $kernel->output();

@unlink(__FILE__);
