<?php

declare(strict_types=1);

// One-shot self-deleting deploy v1.17.0 #230 : git pull + migrate + caches clear.
// Pattern : proc_open (cf. shell API KO en cron classique).

$logFile = __DIR__ . '/../_deploy_230_book_promo.log';
file_put_contents($logFile, "START " . date('c') . PHP_EOL);

function runCmd(string $cmd, string $cwd, string $logFile): array
{
    file_put_contents($logFile, "RUN $cmd (cwd=$cwd)" . PHP_EOL, FILE_APPEND);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, $cwd);
    if (! is_resource($proc)) {
        return ['code' => -1, 'out' => 'proc_open failed'];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    $combined = $out . $err;
    file_put_contents($logFile, " -> code=$code out=" . substr($combined, 0, 2000) . PHP_EOL, FILE_APPEND);
    return ['code' => $code, 'out' => $combined];
}

$root = realpath(__DIR__ . '/..');
$artisan = $root . '/artisan';
$php = '/usr/local/bin/php';
if (! is_file($php)) { $php = 'php'; }
$git = '/usr/local/cpanel/3rdparty/lib/path-bin/git';
if (! is_file($git)) { $git = 'git'; }

// Étape 1 : git pull
$step1 = runCmd("$git pull origin master 2>&1", $root, $logFile);

// Étape 2 : migrate (force pour non-interactive prod)
$step2 = runCmd("$php $artisan migrate --force 2>&1", $root, $logFile);

// Étape 3 : view:clear
$step3 = runCmd("$php $artisan view:clear 2>&1", $root, $logFile);

// Étape 4 : config:clear
$step4 = runCmd("$php $artisan config:clear 2>&1", $root, $logFile);

// Étape 5 : cache:clear
$step5 = runCmd("$php $artisan cache:clear 2>&1", $root, $logFile);

file_put_contents($logFile, "END " . date('c') . PHP_EOL, FILE_APPEND);

@unlink(__FILE__);

header('Content-Type: text/plain; charset=utf-8');
echo "DEPLOY 230 book-promo v1.17.0\n";
echo "step1_pull=" . $step1['code'] . "\n";
echo "step2_migrate=" . $step2['code'] . "\n";
echo "step3_view=" . $step3['code'] . "\n";
echo "step4_config=" . $step4['code'] . "\n";
echo "step5_cache=" . $step5['code'] . "\n";
echo "----\nstep1: " . substr($step1['out'], 0, 800) . "\n";
echo "----\nstep2: " . substr($step2['out'], 0, 800) . "\n";
echo "----\nstep3: " . substr($step3['out'], 0, 400) . "\n";
echo "----\nstep4: " . substr($step4['out'], 0, 400) . "\n";
echo "----\nstep5: " . substr($step5['out'], 0, 400) . "\n";
echo "self=" . (file_exists(__FILE__) ? 'NO' : 'YES') . "\n";
