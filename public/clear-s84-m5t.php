<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('optimize:clear');
$kernel->call('view:clear');
echo "CACHE CLEARED M5t " . date('Y-m-d H:i:s') . "\n";
@unlink(__FILE__);
