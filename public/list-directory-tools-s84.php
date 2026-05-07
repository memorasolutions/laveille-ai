<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

header('Content-Type: text/plain; charset=utf-8');

// Liste les tables candidates qui pourraient contenir les outils
$candidates = ['directory_resources', 'directory_tools', 'directory_resource', 'tools'];
foreach ($candidates as $t) {
    if (Schema::hasTable($t)) {
        $cols = Schema::getColumnListing($t);
        $count = DB::table($t)->count();
        echo "TABLE $t — $count rows — cols: " . implode(', ', $cols) . "\n";
    }
}

// Liste les outils du répertoire (peu importe le statut, on veut TOUT)
if (Schema::hasTable('directory_resources')) {
    $rows = DB::table('directory_resources')->select('id', 'name', 'url', 'is_published', 'is_external')->orderBy('name')->get();
    echo "\n=== directory_resources (" . count($rows) . " rows) ===\n";
    foreach ($rows as $r) {
        echo str_pad((string) $r->id, 5) . ' | pub=' . ($r->is_published ?? '-') . ' | ' . str_pad((string) $r->name, 50) . ' | ' . ($r->url ?? '') . "\n";
    }
}

@unlink(__FILE__);
