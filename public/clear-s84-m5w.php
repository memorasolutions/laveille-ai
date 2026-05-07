<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('view:clear');
$kernel->call('optimize:clear');
echo "CACHE+VIEW CLEARED M5w " . date('Y-m-d H:i:s') . "\n";
@unlink(__FILE__);
