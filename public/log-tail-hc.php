<?php
header('Content-Type: text/plain');
$log = __DIR__ . '/../storage/logs/laravel-' . date('Y-m-d') . '.log';
if (!file_exists($log)) { echo "No log"; exit; }
$lines = file($log);
$tail = array_slice($lines, -50);
foreach ($tail as $l) {
    if (strpos($l, 'production.ERROR') !== false || strpos($l, 'health-check') !== false || strpos($l, 'HealthCheck') !== false) {
        echo htmlspecialchars($l);
    }
}
