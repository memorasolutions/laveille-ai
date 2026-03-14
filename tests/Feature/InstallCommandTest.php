<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Artisan;

test('app:install command exists and is registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('app:install');
});

test('app:install command has correct signature', function () {
    $command = Artisan::all()['app:install'];

    expect($command->getName())->toBe('app:install')
        ->and($command->getDescription())->not->toBeEmpty();
});

test('app:install command has force option', function () {
    $command = Artisan::all()['app:install'];
    $definition = $command->getDefinition();

    expect($definition->hasOption('force'))->toBeTrue();
});
