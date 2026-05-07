<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$kernel->call('cache:clear');
$kernel->call('config:clear');
$kernel->call('route:clear');
$kernel->call('view:clear');
$kernel->call('optimize:clear');
echo "ARTISAN CACHES CLEARED " . date('H:i:s') . "\n";
echo $kernel->output();
@unlink(__FILE__);
