<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
$composerLoader = require __DIR__.'/../vendor/autoload.php';

// Modules nwidart : extension PSR-4 runtime pour modules ajoutés sans composer dump-autoload
// (cPanel sans shell : composer dump impossible). Itère Modules/*/composer.json et merge psr-4.
if ($composerLoader instanceof \Composer\Autoload\ClassLoader) {
    foreach (glob(__DIR__.'/../Modules/*/composer.json') as $modComposer) {
        $modDir = dirname($modComposer);
        $modData = json_decode((string) file_get_contents($modComposer), true);
        $psr4 = $modData['autoload']['psr-4'] ?? [];
        foreach ($psr4 as $namespace => $path) {
            $absPath = $modDir.'/'.ltrim($path, '/');
            if (is_dir($absPath)) {
                $composerLoader->addPsr4($namespace, $absPath);
            }
        }
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
