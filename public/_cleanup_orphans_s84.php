<?php
// One-shot self-deleting cleanup for S84 orphan public scripts (non-tracked
// remains after git rm batch). Deletes itself first, then targets.
@unlink(__FILE__);

header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__;
$patterns = [
    'audit-final-screenshots-s84.php',
    'audit-no-screenshot-s84.php',
    'list-tools-need-screenshot-s84.php',
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
foreach ($patterns as $f) {
    $p = $dir . '/' . $f;
    if (file_exists($p)) {
        @unlink($p);
        echo "DEL $f\n";
        $deleted++;
    } else {
        $missing++;
    }
}

echo "\n=== DONE ===\nDeleted: $deleted\nMissing: $missing\nSelf-deleted: " . (file_exists(__FILE__) ? 'NO' : 'YES') . "\n";
