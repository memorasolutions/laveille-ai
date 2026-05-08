<?php
header('Content-Type: text/plain');
$log = __DIR__ . '/../storage/logs/laravel.log';
$dailyLog = __DIR__ . '/../storage/logs/laravel-' . date('Y-m-d') . '.log';
foreach ([$dailyLog, $log] as $f) {
    if (file_exists($f)) {
        echo "=== $f (size " . filesize($f) . ") ===\n";
        $lines = file($f);
        foreach (array_slice($lines, -100) as $l) {
            if (stripos($l, 'health-check') !== false || stripos($l, 'HealthCheck') !== false || stripos($l, 'Brevo') !== false || stripos($l, 'Mail') !== false) {
                echo substr($l, 0, 300) . "\n";
            }
        }
    } else {
        echo "$f : NO FILE\n";
    }
}
$lock = __DIR__ . '/../storage/app/health-check-report.lock';
echo "\nLock file: " . (file_exists($lock) ? 'EXISTS (age ' . (time() - filemtime($lock)) . 's)' : 'ABSENT') . "\n";
@unlink(__FILE__);
