<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain; charset=utf-8');
$tools = Tool::where('url', 'like', '%opus%')->orWhereJsonContains('name->fr_CA', 'Opus')->orWhereJsonContains('name->fr_CA', 'OpusClip')->orWhere('slug->fr_CA', 'like', '%opus%')->get(['id','name','slug','url','status','lifecycle_status']);
foreach ($tools as $t) {
    echo "id={$t->id} | status={$t->status} | lifecycle=" . ($t->lifecycle_status ?? 'NULL') . "\n";
    echo "  name=" . ($t->getTranslation('name', 'fr_CA') ?? '?') . "\n";
    echo "  slug=" . ($t->getTranslation('slug', 'fr_CA') ?? '?') . "\n";
    echo "  url=" . $t->url . "\n\n";
}
@unlink(__FILE__);
