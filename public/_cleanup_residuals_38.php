<?php
@unlink(__FILE__);
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__;
$targets = [
    'test-hc-v3.php',
    'log-tail-hc.php',
    'log-detect.php',
    'log-mail-tail.php',
    'clear-s84-27-28.php',
    'clear-s84-m5r.php',
    'clear-s84-m5s.php',
    'clear-s84-m5t.php',
    'clear-s84-m5u.php',
    'clear-s84-m5v.php',
    'clear-s84-m5w.php',
    'clear-s84-m5x.php',
];

$deleted = 0;
$missing = 0;
foreach ($targets as $f) {
    $p = $dir . '/' . $f;
    if (file_exists($p)) {
        @unlink($p);
        echo file_exists($p) ? "FAIL $f\n" : "DEL $f\n";
        $deleted++;
    } else {
        $missing++;
    }
}
echo "\n=== DONE ===\nDeleted: $deleted | Missing: $missing\nSelf-deleted: " . (file_exists(__FILE__) ? 'NO' : 'YES') . "\n";
