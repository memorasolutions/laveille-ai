<?php
header('Content-Type: text/plain');
$dir = __DIR__ . '/../storage/logs/';
$files = glob($dir . '*.log');
foreach ($files as $f) echo basename($f) . " - " . filesize($f) . " bytes - " . date('Y-m-d H:i:s', filemtime($f)) . "\n";
@unlink(__FILE__);
