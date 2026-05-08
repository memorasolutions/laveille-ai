<?php
header('Content-Type: text/plain; charset=utf-8');
$dailyLog = __DIR__ . '/../storage/logs/laravel-' . date('Y-m-d') . '.log';
$lock = __DIR__ . '/../storage/app/health-check-report.lock';

echo "=== LOCK FILE ===\n";
if (file_exists($lock)) {
    $age = time() - filemtime($lock);
    echo "EXISTS — created " . date('H:i:s', filemtime($lock)) . " (age {$age}s)\n";
} else {
    echo "ABSENT\n";
}

echo "\n=== LOG TAIL (health-check / brevo / mail / 405 / 502 / Skip) ===\n";
if (file_exists($dailyLog)) {
    $lines = file($dailyLog);
    $tail = array_slice($lines, -300);
    foreach ($tail as $l) {
        if (preg_match('/HealthCheck|health-check|Skip duplicate|Brevo|Mail|brevo|Sent.*health|Lock/', $l)) {
            echo trim($l) . "\n";
        }
    }
}
