<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

// Run sequentially to avoid polluting Modules/ for parallel processes
uses()->group('sequential');

$tempModulesPath = null;
$tempStatusesPath = null;

beforeEach(function () use (&$tempModulesPath, &$tempStatusesPath) {
    // Create an isolated temp directory for the test module
    // This prevents nwidart/modules from scanning TestModule in the real Modules/ folder
    $tempModulesPath = sys_get_temp_dir().'/laravel_test_modules_'.getmypid();
    $tempStatusesPath = $tempModulesPath.'/modules_statuses.json';

    File::ensureDirectoryExists($tempModulesPath);

    // Copy current statuses to temp
    $realPath = base_path('modules_statuses.json');
    if (File::exists($realPath)) {
        File::copy($realPath, $tempStatusesPath);
    }

    // Redirect both the modules path and statuses file to temp
    Config::set('modules.paths.modules', $tempModulesPath);
    Config::set('modules.activators.file.statuses-file', $tempStatusesPath);
});

afterEach(function () use (&$tempModulesPath) {
    // Clean up entire temp directory
    if ($tempModulesPath && File::exists($tempModulesPath)) {
        File::deleteDirectory($tempModulesPath);
    }

    // Also clean up any leftover in real Modules/ (defensive)
    $modulePath = base_path('Modules/TestModule');
    if (File::exists($modulePath)) {
        File::deleteDirectory($modulePath);
    }
});

test('make-module command is registered', function () {
    expect(\Illuminate\Support\Facades\Artisan::all())->toHaveKey('app:make-module');
});

test('make-module creates module directory structure', function () use (&$tempModulesPath) {
    $this->artisan('app:make-module', ['name' => 'TestModule'])
        ->assertExitCode(0);

    $base = "{$tempModulesPath}/TestModule";

    expect(File::exists($base))->toBeTrue()
        ->and(File::exists("{$base}/module.json"))->toBeTrue()
        ->and(File::exists("{$base}/plugin.json"))->toBeTrue()
        ->and(File::exists("{$base}/composer.json"))->toBeTrue()
        ->and(File::exists("{$base}/app/Providers/TestModuleServiceProvider.php"))->toBeTrue()
        ->and(File::exists("{$base}/app/Providers/RouteServiceProvider.php"))->toBeTrue()
        ->and(File::exists("{$base}/routes/web.php"))->toBeTrue()
        ->and(File::exists("{$base}/routes/api.php"))->toBeTrue()
        ->and(File::exists("{$base}/config/config.php"))->toBeTrue()
        ->and(File::exists("{$base}/tests/Feature/TestModuleTest.php"))->toBeTrue();
});

test('make-module fails if module already exists', function () use (&$tempModulesPath) {
    File::makeDirectory("{$tempModulesPath}/TestModule", 0755, true);

    $this->artisan('app:make-module', ['name' => 'TestModule'])
        ->assertExitCode(1);
});

test('make-module updates modules_statuses.json', function () use (&$tempStatusesPath) {
    $this->artisan('app:make-module', ['name' => 'TestModule'])
        ->assertExitCode(0);

    $statuses = json_decode(File::get($tempStatusesPath), true);

    expect($statuses)->toHaveKey('TestModule')
        ->and($statuses['TestModule'])->toBeTrue();
});
