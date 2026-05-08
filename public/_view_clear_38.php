<?php
@unlink(__FILE__);
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
header('Content-Type: text/plain');
$kernel->call('view:clear');
echo "view:clear OK\n";
$kernel->call('cache:clear');
echo "cache:clear OK\n";
if (function_exists('opcache_reset')) { opcache_reset(); echo "opcache OK\n"; }
echo "self-deleted: " . (file_exists(__FILE__) ? 'NO' : 'YES') . "\n";
