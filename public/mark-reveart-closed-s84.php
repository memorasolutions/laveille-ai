<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain');
$tool = Tool::where('slug->fr_CA', 'reve-art')->first();
if (! $tool) { echo "TOOL NOT FOUND"; exit; }
$tool->lifecycle_status = 'closed';
$tool->lifecycle_date = '2026-05-07';
$tool->lifecycle_notes = 'Site indisponible — port 443 refused (vérifié 2026-05-07). DNS résout vers 217.70.184.38 (parking Gandi, même IP que Redaction.io). Service apparemment discontinué.';
$tool->save();
echo "UPDATED Reve.art (id={$tool->id}) → lifecycle=closed\n";
@unlink(__FILE__);
