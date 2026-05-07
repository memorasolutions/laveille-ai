<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$kernel->call('cache:clear');
$kernel->call('config:clear');
$kernel->call('optimize:clear');
echo "CLEARED\n";
@unlink(__FILE__);
