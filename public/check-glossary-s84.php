<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

$searches = [
    ['col' => 'name',  'val' => 'cloud'],
    ['col' => 'slug',  'val' => 'cloud'],
    ['col' => 'name',  'val' => 'IA souveraine'],
    ['col' => 'slug',  'val' => 'ia-souveraine'],
    ['col' => 'name',  'val' => 'souveraine'],
];

$tables = ['dictionary_terms'];
foreach ($tables as $t) {
    if (!\Schema::hasTable($t)) {
        echo "TABLE $t MISSING\n";
        continue;
    }
    echo "=== $t ===\n";
    $cols = DB::select("DESCRIBE $t");
    $colsList = array_map(fn($c) => $c->Field, $cols);
    echo "Columns: " . implode(', ', $colsList) . "\n\n";
    foreach ($searches as $s) {
        if (in_array($s['col'], $colsList)) {
            $rows = DB::table($t)->where($s['col'], 'like', '%' . $s['val'] . '%')->select('id', 'name', 'slug')->get();
            echo "Search " . $s['col'] . " LIKE %" . $s['val'] . "% : " . count($rows) . " row(s)\n";
            foreach ($rows as $r) {
                echo "  - id={$r->id} name='{$r->name}' slug='{$r->slug}'\n";
            }
        }
    }
}
@unlink(__FILE__);
