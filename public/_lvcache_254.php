<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca>
 *
 * #254 — Cache-clear one-shot post-deploy v1.19.15 (register anti-enum).
 * Self-deleting : ce fichier supprime son propre code après une exécution
 * réussie pour ne pas laisser de surface d'attaque post-deploy.
 *
 * Token-protégé via LV_GIT_TOKEN .env (réutilise le secret existant).
 */

// Lit token .env sans booter Laravel (rapide).
$envPath = __DIR__.'/../.env';
$envContent = @file_get_contents($envPath);
if ($envContent === false) {
    http_response_code(500);
    exit('env unreadable');
}
if (!preg_match('/^LV_GIT_TOKEN=(.+)$/m', $envContent, $m)) {
    http_response_code(500);
    exit('token not configured');
}
$expected = trim($m[1]);
$provided = (string)($_GET['t'] ?? '');
if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

chdir(__DIR__.'/..');

$artisan = ['/usr/local/bin/php', 'artisan'];
$cmds = [
    array_merge($artisan, ['view:clear']),
    array_merge($artisan, ['route:clear']),
    array_merge($artisan, ['config:clear']),
    array_merge($artisan, ['cache:clear']),
];

$allOk = true;
foreach ($cmds as $cmd) {
    echo '$ '.implode(' ', $cmd)."\n";
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (is_resource($proc)) {
        echo stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        if ($err) {
            echo "STDERR: ".$err;
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $rc = proc_close($proc);
        if ($rc !== 0) {
            $allOk = false;
        }
    } else {
        echo "Failed to spawn\n";
        $allOk = false;
    }
}

echo "\n--- ".($allOk ? 'OK' : 'PARTIAL')." ".date('c')."\n";

// Self-delete (one-shot) to avoid leaving an attack surface.
@unlink(__FILE__);
echo "Self-deleted: ".basename(__FILE__)."\n";
