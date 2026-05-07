<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain; charset=utf-8');

// Tente plusieurs slugs / patterns name
$tool = Tool::where('slug->fr_CA', 'opus-pro')
    ->orWhere('slug->fr_CA', 'opusclip')
    ->orWhere('slug->fr_CA', 'opus-clip')
    ->orWhereJsonContains('name->fr_CA', 'OpusClip')
    ->orWhereJsonContains('name->fr_CA', 'Opus.pro')
    ->orWhere('url', 'like', '%opus.pro%')
    ->first();

if (! $tool) { echo "TOOL NOT FOUND"; exit; }

$old = $tool->url;
$tool->url = 'https://www.opus.pro/fr-fr';
$tool->save();
echo "UPDATED OpusClip (id={$tool->id})\n";
echo "  Old: $old\n";
echo "  New: https://www.opus.pro/fr-fr\n";
@unlink(__FILE__);
