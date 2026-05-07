<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain');
$dup = Tool::find(764);
if (! $dup) { echo "id=764 not found"; exit; }
$dup->status = 'draft';
$dup->lifecycle_status = 'closed';
$dup->lifecycle_notes = 'DOUBLON de id=86 (OpusClip) — fusionné 2026-05-07';
$dup->save();
echo "DEDUPED id=764 → status=draft + lifecycle=closed\n";
echo "Reste id=86 OpusClip url=https://www.opus.pro/fr-fr (canonique)\n";
@unlink(__FILE__);
