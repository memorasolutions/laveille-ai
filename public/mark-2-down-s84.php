<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Directory\Models\Tool;
header('Content-Type: text/plain');

$updates = [
    ['url_like' => '%debuild.app%', 'note' => 'Domaine debuild.app NXDOMAIN (vérifié 2026-05-07). Service éteint.'],
    ['url_like' => '%hn.tin-sever.de%', 'note' => 'Backend retourne 502 Bad Gateway persistent (vérifié 2026-05-07). Service Lazy-HN down.'],
    ['url_like' => '%lazy-hn%', 'note' => 'Variante slug Lazy-HN — service down (vérifié 2026-05-07).'],
];

foreach ($updates as $u) {
    $tools = Tool::where('url', 'like', $u['url_like'])->get();
    foreach ($tools as $t) {
        $t->lifecycle_status = 'closed';
        $t->lifecycle_date = '2026-05-07';
        $t->lifecycle_notes = $u['note'];
        $t->save();
        echo "UPDATED id={$t->id} name=" . ($t->getTranslation('name', 'fr_CA') ?? '?') . " url={$t->url} → closed\n";
    }
}
@unlink(__FILE__);
