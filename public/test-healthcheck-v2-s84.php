<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

set_time_limit(180);
header('Content-Type: text/plain; charset=utf-8');

// Capture output buffered
ob_start();
$exitCode = $kernel->call('directory:health-check-report', [
    '--limit' => 10,
    '--no-email' => true,
]);
$artisanOutput = $kernel->output();
$bufferedOutput = ob_get_clean();

echo "=== ARTISAN OUTPUT ===\n";
echo $artisanOutput ?: '(empty)';
echo "\n=== BUFFERED ===\n";
echo $bufferedOutput ?: '(empty)';
echo "\n=== EXIT CODE: $exitCode ===\n";

@unlink(__FILE__);
