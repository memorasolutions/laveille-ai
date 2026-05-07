<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');
$row = DB::table('dictionary_terms')->where('id', 27)->first();
echo "=== Term id=27 (Cloud computing) full row ===\n";
foreach ((array) $row as $k => $v) {
    echo "$k = " . (is_string($v) && strlen($v) > 200 ? substr($v, 0, 200) . '...[' . strlen($v) . ']' : (string) $v) . "\n\n";
}
// Count published terms with hero_image
$counts = [
    'total' => DB::table('dictionary_terms')->count(),
    'published' => DB::table('dictionary_terms')->where('is_published', 1)->count(),
    'with_hero_image' => DB::table('dictionary_terms')->whereNotNull('hero_image')->count(),
];
echo "\nCounts: " . json_encode($counts);
@unlink(__FILE__);
