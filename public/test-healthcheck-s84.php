<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

set_time_limit(180);
header('Content-Type: text/plain; charset=utf-8');

// Run command --limit=10 --no-email pour smoke prod
$exitCode = $kernel->call('directory:health-check-report', [
    '--limit' => 10,
    '--no-email' => true,
]);
echo "\nExit code: $exitCode\n";
echo $kernel->output();

@unlink(__FILE__);
