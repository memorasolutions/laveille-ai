<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

// Run sequentially to avoid polluting modules_statuses.json for parallel tests
uses()->group('sequential');

$originalStatuses = null;

beforeEach(function () use (&$originalStatuses) {
    $statusesPath = base_path('modules_statuses.json');
    if (File::exists($statusesPath)) {
        $originalStatuses = File::get($statusesPath);
    }

    // Defensive cleanup: remove leftover TestModule from a crashed previous run
    $modulePath = base_path('Modules/TestModule');
    if (File::exists($modulePath)) {
        File::deleteDirectory($modulePath);
    }
});

afterEach(function () use (&$originalStatuses) {
    try {
        // FIRST: restore modules_statuses.json IMMEDIATELY to prevent race conditions
        // Other parallel processes read this file at boot; it must not contain TestModule
        $statusesPath = base_path('modules_statuses.json');
        if ($originalStatuses !== null) {
            File::put($statusesPath, $originalStatuses);
        }
    } finally {
        // THEN: clean up the TestModule directory
        $modulePath = base_path('Modules/TestModule');
        if (File::exists($modulePath)) {
            File::deleteDirectory($modulePath);
        }
    }
});

test('make-module command is registered', function () {
    expect(Artisan::all())->toHaveKey('app:make-module');
});

test('make-module creates module directory structure', function () {
    $this->artisan('app:make-module', ['name' => 'TestModule'])
        ->assertExitCode(0);

    $base = base_path('Modules/TestModule');

    expect(File::exists($base))->toBeTrue()
        ->and(File::exists("{$base}/module.json"))->toBeTrue()
        ->and(File::exists("{$base}/plugin.json"))->toBeTrue()
        ->and(File::exists("{$base}/composer.json"))->toBeTrue()
        ->and(File::exists("{$base}/app/Providers/TestModuleServiceProvider.php"))->toBeTrue()
        ->and(File::exists("{$base}/app/Providers/RouteServiceProvider.php"))->toBeTrue()
        ->and(File::exists("{$base}/app/Providers/EventServiceProvider.php"))->toBeTrue()
        ->and(File::exists("{$base}/routes/web.php"))->toBeTrue()
        ->and(File::exists("{$base}/routes/api.php"))->toBeTrue()
        ->and(File::exists("{$base}/config/config.php"))->toBeTrue()
        ->and(File::exists("{$base}/tests/Feature/TestModuleTest.php"))->toBeTrue();
});

test('make-module fails if module already exists', function () {
    File::makeDirectory(base_path('Modules/TestModule'), 0755, true);

    $this->artisan('app:make-module', ['name' => 'TestModule'])
        ->assertExitCode(1);
});

test('make-module updates modules_statuses.json', function () {
    $this->artisan('app:make-module', ['name' => 'TestModule'])
        ->assertExitCode(0);

    $statuses = json_decode(File::get(base_path('modules_statuses.json')), true);

    expect($statuses)->toHaveKey('TestModule')
        ->and($statuses['TestModule'])->toBeTrue();
});
