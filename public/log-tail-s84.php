<?php
$log = __DIR__ . '/../storage/logs/laravel-' . date('Y-m-d') . '.log';
if (!file_exists($log)) { echo "no log"; exit; }
$lines = file($log);
$tail = array_slice($lines, -80);
echo "<pre>";
foreach ($tail as $l) {
    if (strpos($l, 'production.ERROR') !== false || strpos($l, 'syntax error') !== false || strpos($l, 'Stack trace') !== false || strpos($l, '#') === 0) {
        echo htmlspecialchars($l);
    }
}
echo "</pre>";
@unlink(__FILE__);
