<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain');
$tools = Tool::where('url', 'like', '%play.ht%')
    ->orWhere('url', 'like', '%playht%')
    ->orWhereJsonContains('name->fr_CA', 'PlayHT')
    ->orWhereJsonContains('name->fr_CA', 'Play.ht')
    ->orWhereJsonContains('name->fr_CA', 'Play HT')
    ->orWhere('slug->fr_CA', 'like', '%playht%')
    ->orWhere('slug->fr_CA', 'like', '%play-ht%')
    ->get();
if ($tools->isEmpty()) { echo "NO PlayHT tool found"; exit; }
foreach ($tools as $t) {
    $t->lifecycle_status = 'closed';
    $t->lifecycle_date = '2026-05-07';
    $t->lifecycle_notes = 'Domaine play.ht ne résout plus (NXDOMAIN, vérifié 2026-05-07). Variantes www.play.ht et playht.com aussi down. Service apparemment discontinué ou domaine perdu.';
    $t->save();
    echo "UPDATED id={$t->id} name=" . ($t->getTranslation('name', 'fr_CA') ?? '?') . " url={$t->url} → lifecycle=closed\n";
}
@unlink(__FILE__);
