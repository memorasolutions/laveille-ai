<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('check command is registered', function () {
    expect(Artisan::all())->toHaveKey('app:check');
});

test('check command runs successfully with quick flag', function () {
    $this->artisan('app:check', ['--quick' => true])
        ->assertExitCode(0);
});

test('check command has quick option', function () {
    $command = Artisan::all()['app:check'];

    expect($command->getDefinition()->hasOption('quick'))->toBeTrue();
});

test('quick flag skips PHPStan and tests output', function () {
    Artisan::call('app:check', ['--quick' => true]);
    $output = Artisan::output();

    expect($output)
        ->not->toContain('PHPStan')
        ->not->toContain('Tests');
});
