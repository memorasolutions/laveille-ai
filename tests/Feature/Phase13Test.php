<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Task 1: Scheduler
test('scheduler has backup:run command', function () {
    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    expect($events->contains(fn ($e) => str_contains($e->command ?? '', 'backup:run')))->toBeTrue();
});

test('scheduler has health:check command', function () {
    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    expect($events->contains(fn ($e) => str_contains($e->command ?? '', 'health:check')))->toBeTrue();
});

test('scheduler has horizon:snapshot command', function () {
    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    expect($events->contains(fn ($e) => str_contains($e->command ?? '', 'horizon:snapshot')))->toBeTrue();
});

// Task 2: Admin controllers exist
test('admin dashboard controller exists', function () {
    expect(class_exists(\Modules\Backoffice\Http\Controllers\DashboardController::class))->toBeTrue();
});

test('admin user controller exists', function () {
    expect(class_exists(\Modules\Backoffice\Http\Controllers\UserController::class))->toBeTrue();
});

// Task 3: Admin role controller exists
test('admin role controller exists', function () {
    expect(class_exists(\Modules\Backoffice\Http\Controllers\RoleController::class))->toBeTrue();
});

// Task 4: Admin views and layout
test('admin layout exists', function () {
    expect(file_exists(base_path('Modules/Backoffice/resources/views/layouts/admin.blade.php')))->toBeTrue();
});

test('admin sidebar partial exists', function () {
    expect(file_exists(base_path('Modules/Backoffice/resources/views/partials/sidebar.blade.php')))->toBeTrue();
});

test('admin topbar partial exists', function () {
    expect(file_exists(base_path('Modules/Backoffice/resources/views/partials/topbar.blade.php')))->toBeTrue();
});

// Task 5: Stubs cleaned
test('stub controllers have been removed', function () {
    $stubs = [
        'Modules/Core/app/Http/Controllers/CoreController.php',
        'Modules/Auth/app/Http/Controllers/AuthController.php',
        'Modules/Settings/app/Http/Controllers/SettingsController.php',
        'Modules/Logging/app/Http/Controllers/LoggingController.php',
        'Modules/Backoffice/app/Http/Controllers/BackofficeController.php',
    ];

    foreach ($stubs as $stub) {
        expect(file_exists(base_path($stub)))->toBeFalse("Stub $stub should be deleted");
    }
});

test('base api controller is preserved', function () {
    expect(file_exists(base_path('Modules/Api/app/Http/Controllers/BaseApiController.php')))->toBeTrue();
});

// Task 6: .env.example
test('env example has all required sections', function () {
    $env = file_get_contents(base_path('.env.example'));

    expect($env)->toContain('HORIZON_PREFIX')
        ->toContain('TELESCOPE_ENABLED')
        ->toContain('BACKUP_NAME')
        ->toContain('HEALTH_DISK')
        ->toContain('FORCE_HTTPS');
});

// Task 7: Email template + deploy
test('welcome email template exists', function () {
    expect(file_exists(resource_path('views/emails/welcome.blade.php')))->toBeTrue();
});

test('deploy script exists and is executable', function () {
    $path = base_path('scripts/deploy.sh');

    expect(file_exists($path))->toBeTrue();
    expect(is_executable($path))->toBeTrue();
});
