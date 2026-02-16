<?php

declare(strict_types=1);

use Modules\Backoffice\Filament\Widgets\LatestActivity;
use Modules\Backoffice\Filament\Widgets\StatsOverview;
use Modules\Backoffice\Filament\Widgets\SystemInfo;

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

// Task 2: UserResource
test('user resource class exists', function () {
    expect(class_exists(\Modules\Auth\Filament\Resources\UserResource::class))->toBeTrue();
});

test('user resource has correct model', function () {
    expect(\Modules\Auth\Filament\Resources\UserResource::getModel())->toBe(\App\Models\User::class);
});

test('user resource has list, create and edit pages', function () {
    $pages = \Modules\Auth\Filament\Resources\UserResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

// Task 3: RoleResource
test('role resource class exists', function () {
    expect(class_exists(\Modules\RolesPermissions\Filament\Resources\RoleResource::class))->toBeTrue();
});

test('role resource has correct model', function () {
    expect(\Modules\RolesPermissions\Filament\Resources\RoleResource::getModel())
        ->toBe(\Spatie\Permission\Models\Role::class);
});

test('role resource has list, create and edit pages', function () {
    $pages = \Modules\RolesPermissions\Filament\Resources\RoleResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

// Task 4: Widgets
test('stats overview widget exists', function () {
    expect(class_exists(StatsOverview::class))->toBeTrue();
    expect(is_subclass_of(StatsOverview::class, \Filament\Widgets\StatsOverviewWidget::class))->toBeTrue();
});

test('latest activity widget exists', function () {
    expect(class_exists(LatestActivity::class))->toBeTrue();
    expect(is_subclass_of(LatestActivity::class, \Filament\Widgets\TableWidget::class))->toBeTrue();
});

test('system info widget exists', function () {
    expect(class_exists(SystemInfo::class))->toBeTrue();
    expect(is_subclass_of(SystemInfo::class, \Filament\Widgets\Widget::class))->toBeTrue();
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
        ->toContain('FILAMENT_PATH')
        ->toContain('FORCE_HTTPS');
});

// Task 7: Email template + deploy
test('base email template exists', function () {
    expect(file_exists(resource_path('views/emails/base.blade.php')))->toBeTrue();
});

test('deploy script exists and is executable', function () {
    $path = base_path('scripts/deploy.sh');

    expect(file_exists($path))->toBeTrue();
    expect(is_executable($path))->toBeTrue();
});
