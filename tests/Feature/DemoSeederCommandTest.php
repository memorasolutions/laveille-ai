<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('app:demo command is registered', function () {
    expect(Artisan::all())->toHaveKey('app:demo');
});

test('app:demo creates demo users', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->artisan('app:demo')->assertExitCode(0);

    expect(User::where('email', 'editor@demo.test')->exists())->toBeTrue()
        ->and(User::where('email', 'user@demo.test')->exists())->toBeTrue()
        ->and(User::where('email', 'premium@demo.test')->exists())->toBeTrue();
});

test('app:demo is idempotent', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->artisan('app:demo')->assertExitCode(0);
    $this->artisan('app:demo')->assertExitCode(0);

    expect(User::where('email', 'like', '%@demo.test')->count())->toBe(3);
});
