<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Artisan;

afterEach(function () {
    $hook = base_path('.git/hooks/pre-commit');
    if (file_exists($hook)) {
        unlink($hook);
    }
});

test('setup-hooks command is registered', function () {
    expect(Artisan::all())->toHaveKey('app:setup-hooks');
});

test('setup-hooks command has force option', function () {
    $command = Artisan::all()['app:setup-hooks'];

    expect($command->getDefinition()->hasOption('force'))->toBeTrue();
});

test('setup-hooks installs hook with force flag', function () {
    $this->artisan('app:setup-hooks', ['--force' => true])
        ->assertExitCode(0);

    expect(file_exists(base_path('.git/hooks/pre-commit')))->toBeTrue();
});

test('setup-hooks fails if source script missing', function () {
    $source = base_path('scripts/pre-commit');
    $backup = $source.'.bak';

    rename($source, $backup);

    try {
        $result = Artisan::call('app:setup-hooks', ['--force' => true]);
        expect($result)->toBe(1);
    } finally {
        rename($backup, $source);
    }
});
