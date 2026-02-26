<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('status command is registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('app:status');
});

test('status command runs successfully', function () {
    $result = Artisan::call('app:status');

    expect($result)->toBe(0);
});

test('status command outputs required sections', function () {
    Artisan::call('app:status');
    $output = Artisan::output();

    expect($output)
        ->toContain('Application')
        ->toContain('PHP Version')
        ->toContain('Database')
        ->toContain('Cache');
});
