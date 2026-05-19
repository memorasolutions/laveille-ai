<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca>
 *
 * #250 — Endpoint git-pull token-protégé pour resync prod avec origin/master.
 * Évite les multiples cpanel_file_write par déploiement quand Shell API cPanel
 * est désactivée. Usage : curl "https://laveille.ai/_lvgit.php?t=$LV_GIT_TOKEN"
 * Restrictions : token .env LV_GIT_TOKEN (64-char hex), commandes git
 * allowlist uniquement (fetch + reset --hard + log).
 */

// Lit le token depuis .env sans booter Laravel (faster + no autoload required).
$envPath = __DIR__.'/../.env';
$envContent = @file_get_contents($envPath);
if ($envContent === false) {
    http_response_code(500);
    exit('env unreadable');
}

if (!preg_match('/^LV_GIT_TOKEN=(.+)$/m', $envContent, $matches)) {
    http_response_code(500);
    exit('token not configured');
}
$expectedToken = trim($matches[1]);

$providedToken = (string)($_GET['t'] ?? '');
if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

chdir(__DIR__.'/..');

$commands = [
    ['/usr/bin/git', 'fetch', '--quiet', 'origin'],
    ['/usr/bin/git', 'reset', '--hard', 'origin/master'],
    ['/usr/bin/git', 'log', '-1', '--oneline'],
    ['/usr/bin/git', 'status', '-s'],
];

foreach ($commands as $cmd) {
    echo '$ '.implode(' ', $cmd)."\n";
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (is_resource($proc)) {
        echo stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        if ($stderr !== '') {
            echo "stderr: ".$stderr;
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
    } else {
        echo "[proc_open failed]\n";
    }
    echo "\n";
}

echo "OK ".date('c')."\n";
