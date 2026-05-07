<?php
/**
 * Script PHP web one-shot S83 #226 — Exécution OqlfAiTermsSeeder en prod.
 * Workaround cpanel_terminal MCP KO (S80+).
 * Self-delete via @unlink(__FILE__) en fin d'exécution.
 *
 * @author MEMORA solutions
 * @session S83
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $seeder = new \Modules\Dictionary\Database\Seeders\OqlfAiTermsSeeder;
    $seeder->run();

    $count = \Modules\Dictionary\Models\Term::whereIn('slug->fr_CA', [
        'affinage',
        'infiltration-de-requete',
        'debridage-dia',
        'agent-autonome',
        'systeme-multiagent',
    ])->count();

    echo "OK — OqlfAiTermsSeeder exécuté. Termes en DB : {$count}/5\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'KO — '.$e->getMessage()."\n".$e->getTraceAsString();
} finally {
    @unlink(__FILE__);
}
