<?php
header('Content-Type: text/plain');
$log = __DIR__ . '/../storage/logs/laravel-' . date('Y-m-d') . '.log';
if (!file_exists($log)) { echo "no log"; exit; }
$lines = file($log);
$tail = array_slice($lines, -300);
foreach ($tail as $l) {
    if (strpos($l, 'HealthCheck') !== false || strpos($l, 'Brevo') !== false || strpos($l, 'health-check') !== false || strpos($l, 'Mail') !== false || strpos($l, 'BrevoApiTransport') !== false) {
        echo htmlspecialchars($l);
    }
}
