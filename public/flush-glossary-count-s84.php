<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\Cache::forget('dictionary_terms_count');
$kernel->call('view:clear');
$kernel->call('optimize:clear');
$count = \Modules\Dictionary\Models\Term::where('is_published', 1)->count();
echo "FLUSHED, terms count = $count\n";
@unlink(__FILE__);
