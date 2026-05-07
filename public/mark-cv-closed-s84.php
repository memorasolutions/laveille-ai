<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain');
$tools = Tool::where('url', 'like', '%customersvoices%')->get();
if ($tools->isEmpty()) { echo "NO tool found"; exit; }
foreach ($tools as $t) {
    $t->lifecycle_status = 'closed';
    $t->lifecycle_date = '2026-05-07';
    $t->lifecycle_notes = 'Site indisponible — timeout 15s sur port 443 (vérifié 2026-05-07). DNS résout vers 98.84.163.15 (AWS) mais serveur ne répond plus. Service probablement éteint.';
    $t->save();
    echo "UPDATED id={$t->id} name=" . ($t->getTranslation('name', 'fr_CA') ?? '?') . " url={$t->url} → lifecycle=closed\n";
}
@unlink(__FILE__);
