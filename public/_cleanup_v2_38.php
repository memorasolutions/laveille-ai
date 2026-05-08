<?php
@unlink(__FILE__);
header('Content-Type: text/plain; charset=utf-8');
$dir = __DIR__;
$targets = ['test-hc-v3.php', 'log-tail-hc.php'];
$out = [];
foreach ($targets as $f) {
    $p = $dir . '/' . $f;
    if (file_exists($p)) {
        @unlink($p);
        $out[] = file_exists($p) ? "FAIL $f" : "DEL $f";
    } else {
        $out[] = "MISS $f";
    }
}
echo implode("\n", $out) . "\nself=" . (file_exists(__FILE__) ? 'NO' : 'YES') . "\n";
