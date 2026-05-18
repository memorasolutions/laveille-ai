<?php
/**
 * Script PHP web one-shot S96 #238 — Deploy v1.18.4 CWV quick wins mobile.
 * Pattern proc_open (Shell API cpanel KO). Self-delete via @unlink(__FILE__) en fin.
 *
 * Pipeline :
 *   1. git -C / fetch + pull origin master
 *   2. composer dump-autoload --optimize
 *   3. php artisan view:clear (purge Blade cache CRITIQUE — preload/fetchpriority changes)
 *   4. php artisan config:clear (purge config cache)
 *   5. php artisan route:clear (CRITIQUE — nouveau cacheResponse middleware sur routes Directory/Tools/Dictionary/News)
 *   6. php artisan responsecache:clear (purge Spatie ResponseCache existant — invalide tout pour catch new TTLs)
 *   7. php artisan optimize (recompile config + routes + views)
 *   8. echo verify SHA + lv_version() == 1.18.4
 *
 * @author MEMORA solutions
 * @session S96
 * @issue  #238
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
echo "=== DEPLOY v1.18.4 #238 CWV quick wins ===\n\n";

$root = realpath(__DIR__.'/..');
chdir($root);

function run(string $cmd, int $timeout = 60): array {
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptors, $pipes);
    if (! is_resource($proc)) {
        return ['code' => -1, 'out' => '', 'err' => 'proc_open failed'];
    }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $out = '';
    $err = '';
    $start = time();
    while (true) {
        $status = proc_get_status($proc);
        $out .= stream_get_contents($pipes[1]);
        $err .= stream_get_contents($pipes[2]);
        if (! $status['running']) break;
        if (time() - $start > $timeout) {
            proc_terminate($proc);
            break;
        }
        usleep(100000);
    }
    $out .= stream_get_contents($pipes[1]);
    $err .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return ['code' => $code, 'out' => $out, 'err' => $err];
}

$steps = [
    'git fetch'                    => 'git fetch origin master 2>&1',
    'git pull'                     => 'git pull origin master 2>&1',
    'git current SHA'              => 'git rev-parse --short HEAD',
    'composer dump-autoload'       => 'composer dump-autoload --optimize 2>&1',
    'php artisan view:clear'       => 'php artisan view:clear 2>&1',
    'php artisan config:clear'     => 'php artisan config:clear 2>&1',
    'php artisan route:clear'      => 'php artisan route:clear 2>&1',
    'php artisan responsecache:clear' => 'php artisan responsecache:clear 2>&1',
    'php artisan optimize'         => 'php artisan optimize 2>&1',
];

foreach ($steps as $label => $cmd) {
    echo "--- {$label} ---\n";
    $r = run($cmd, 90);
    echo $r['out'];
    if ($r['err']) echo "[stderr] ".$r['err'];
    echo "[code={$r['code']}]\n\n";
}

// Verify version
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (function_exists('lv_version')) {
    $v = lv_version();
    echo "Version applicative : {$v}\n";
    echo (str_contains($v, '1.18.4') ? "[OK]" : "[KO]") . " expect v1.18.4\n";
}

$cfg = config('responsecache.enabled');
echo "Spatie ResponseCache enabled : " . ($cfg ? 'true' : 'false') . "\n";

echo "\n=== DEPLOY DONE — self-delete in 1s ===\n";

@unlink(__FILE__);
