<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

test('app:docs command is registered', function () {
    expect(Artisan::all())->toHaveKey('app:docs');
});

test('app:docs console output includes modules section', function () {
    $this->artisan('app:docs')
        ->assertExitCode(0)
        ->expectsOutputToContain('Modules');
});

test('app:docs console output includes routes section', function () {
    $this->artisan('app:docs')
        ->assertExitCode(0)
        ->expectsOutputToContain('Routes');
});

test('app:docs console output includes permissions section', function () {
    $this->artisan('app:docs')
        ->assertExitCode(0)
        ->expectsOutputToContain('Permissions');
});

test('app:docs console output includes commands section', function () {
    $this->artisan('app:docs')
        ->assertExitCode(0)
        ->expectsOutputToContain('Artisan Commands');
});

test('app:docs console output includes configuration section', function () {
    $this->artisan('app:docs')
        ->assertExitCode(0)
        ->expectsOutputToContain('Configuration');
});

test('app:docs markdown format generates valid output', function () {
    $this->artisan('app:docs', ['--format' => 'markdown'])
        ->assertExitCode(0);
});

test('app:docs markdown file output writes to disk', function () {
    $filePath = storage_path('app/test-docs.md');

    $this->artisan('app:docs', ['--format' => 'markdown', '--output' => $filePath])
        ->assertExitCode(0);

    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)->toContain('# Project Documentation');

    unlink($filePath);
});
