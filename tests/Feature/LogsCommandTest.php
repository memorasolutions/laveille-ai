<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Artisan;

test('logs command is registered', function () {
    expect(Artisan::all())->toHaveKey('app:logs');
});

test('logs command has expected options', function () {
    $command = Artisan::all()['app:logs'];
    $definition = $command->getDefinition();

    expect($definition->hasOption('lines'))->toBeTrue()
        ->and($definition->hasOption('level'))->toBeTrue()
        ->and($definition->hasOption('clear'))->toBeTrue();
});

test('logs command fails when log file does not exist', function () {
    $logPath = storage_path('logs/laravel.log');
    $backup = null;

    if (file_exists($logPath)) {
        $backup = file_get_contents($logPath);
        rename($logPath, $logPath.'.bak');
    }

    try {
        $result = Artisan::call('app:logs');
        expect($result)->toBe(1);
    } finally {
        if ($backup !== null) {
            rename($logPath.'.bak', $logPath);
        }
    }
});
