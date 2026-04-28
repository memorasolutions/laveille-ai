<?php

declare(strict_types=1);

// One-shot self-deleting script : update défi W18 + envoi test newsletter complète
// Pattern L90-V2 : proc_open disponible en web context (exec/shell_exec disable_functions)

$logFile = __DIR__ . '/../_run_defi_w18_test.log';
file_put_contents($logFile, "START " . date('c') . PHP_EOL);

$artisan = realpath(__DIR__ . '/../artisan');
if (! $artisan || ! is_file($artisan)) {
    file_put_contents($logFile, "ERROR artisan not found" . PHP_EOL, FILE_APPEND);
    @unlink(__FILE__);
    echo "ERR artisan";
    exit;
}

function runArtisan(string $artisan, string $cmd, string $logFile): array {
    $cwd = dirname($artisan);
    $php = '/usr/local/bin/php';
    if (! is_file($php)) { $php = 'php'; }
    $full = "$php $artisan $cmd 2>&1";
    file_put_contents($logFile, "RUN $full" . PHP_EOL, FILE_APPEND);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($full, $descriptors, $pipes, $cwd);
    if (! is_resource($proc)) {
        return ['code' => -1, 'out' => 'proc_open failed'];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return ['code' => $code, 'out' => $out . $err];
}

// Étape 1 : update défi W18
$step1 = runArtisan($artisan, 'newsletter:update-defi-w18', $logFile);
file_put_contents($logFile, "STEP1 code={$step1['code']} out={$step1['out']}" . PHP_EOL, FILE_APPEND);

if ($step1['code'] !== 0) {
    file_put_contents($logFile, "ABORT step1 failed" . PHP_EOL, FILE_APPEND);
    @unlink(__FILE__);
    echo "ERR step1: " . $step1['out'];
    exit;
}

// Étape 2 : envoi test newsletter complète à chatgptpro@gomemora.com
$step2 = runArtisan($artisan, 'newsletter:digest --test-email=chatgptpro@gomemora.com --force', $logFile);
file_put_contents($logFile, "STEP2 code={$step2['code']} out={$step2['out']}" . PHP_EOL, FILE_APPEND);

file_put_contents($logFile, "END " . date('c') . PHP_EOL, FILE_APPEND);

// Auto-suppression
@unlink(__FILE__);

echo "DONE step1_code={$step1['code']} step2_code={$step2['code']}" . PHP_EOL;
echo "step1: " . $step1['out'] . PHP_EOL;
echo "step2: " . $step2['out'] . PHP_EOL;
