<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

// directory_tools : table principale (747 outils)
$rows = DB::table('directory_tools')->select('id', 'name', 'slug', 'url', 'aliases', 'status')->orderBy('name')->get();
echo "TOTAL directory_tools: " . count($rows) . "\n\n";
foreach ($rows as $r) {
    $aliases = '';
    if ($r->aliases) {
        $a = json_decode($r->aliases, true);
        if (is_array($a)) $aliases = ' [aliases: ' . implode(', ', $a) . ']';
    }
    $url = $r->url ?? '';
    // Domain only for matching
    $domain = '';
    if ($url && preg_match('#https?://([^/]+)#i', $url, $m)) $domain = strtolower(preg_replace('/^www\./', '', $m[1]));
    echo str_pad((string) $r->id, 5) . ' | ' . str_pad($r->status ?? '-', 12) . ' | ' . str_pad((string) $r->name, 45) . ' | ' . $domain . $aliases . "\n";
}
@unlink(__FILE__);
