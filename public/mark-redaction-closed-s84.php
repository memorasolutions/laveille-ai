<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;

header('Content-Type: text/plain; charset=utf-8');

$tool = Tool::where('slug->fr_CA', 'redaction-io')->first();
if (! $tool) { echo "TOOL NOT FOUND"; exit; }

$tool->lifecycle_status = 'closed';
$tool->lifecycle_date = '2026-05-07';
$tool->lifecycle_notes = 'Site web indisponible — port 443 refused (vérifié 2026-05-07). DNS résout (217.70.184.38, Gandi expire 2026-10-10) mais serveur web éteint. Service apparemment discontinué par l\'éditeur.';
$tool->save();

echo "UPDATED Redaction.io (id={$tool->id})\n";
echo "  lifecycle_status: closed\n";
echo "  lifecycle_date: 2026-05-07\n";
echo "  lifecycle_notes: " . substr($tool->lifecycle_notes, 0, 80) . "...\n";

@unlink(__FILE__);
