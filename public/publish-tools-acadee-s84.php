<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

// Publier les outils créés en S84 batch ACADEE (id 754-794)
$updated = DB::table('directory_tools')
    ->whereBetween('id', [754, 794])
    ->where('status', 'pending')
    ->update(['status' => 'published', 'updated_at' => now()]);

echo "PUBLISHED $updated outils (id 754-794)\n";

// Stats
$counts = DB::table('directory_tools')
    ->select('status', DB::raw('COUNT(*) as n'))
    ->groupBy('status')
    ->get();
foreach ($counts as $c) echo "  status={$c->status}: {$c->n}\n";

// Clear cache
$kernel->call('view:clear');
$kernel->call('optimize:clear');
echo "CACHE CLEARED\n";
@unlink(__FILE__);
