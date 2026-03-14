<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
test('core setup command exists', function () {
    $this->artisan('core:setup --help')
        ->assertSuccessful();
});

test('all modules are loaded', function () {
    $modules = app('modules')->allEnabled();
    $enabledNames = collect($modules)->map(fn ($m) => $m->getName())->toArray();

    $expectedModules = [
        'Core', 'Auth', 'RolesPermissions', 'Settings',
        'Logging', 'Health', 'Storage', 'Media',
        'Notifications', 'Webhooks', 'Api', 'SEO',
        'Backoffice', 'SaaS', 'Tenancy',
        'Backup', 'Translation', 'Export', 'Search',
    ];

    foreach ($expectedModules as $module) {
        expect($enabledNames)->toContain($module);
    }
});

test('saas and tenancy modules are enabled with feature flags defined', function () {
    $enabled = app('modules')->allEnabled();
    $enabledNames = collect($enabled)->map(fn ($m) => $m->getName())->toArray();
    expect($enabledNames)->toContain('SaaS');
    expect($enabledNames)->toContain('Tenancy');

    $defined = \Laravel\Pennant\Feature::defined();
    expect($defined)->toContain('module-saas');
    expect($defined)->toContain('module-tenancy');
});

test('backoffice service provider is registered', function () {
    expect(class_exists(\Modules\Backoffice\Providers\BackofficeServiceProvider::class))->toBeTrue();
});

test('database seeder can run', function () {
    $this->artisan('migrate:fresh');
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $admin = \App\Models\User::where('email', config('app.superadmin_email'))->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasRole('super_admin'))->toBeTrue();
});

test('feature flags are defined for optional modules', function () {
    $defined = \Laravel\Pennant\Feature::defined();
    expect($defined)->toContain('module-saas');
    expect($defined)->toContain('module-tenancy');
});

test('new project script exists and is executable', function () {
    $scriptPath = base_path('scripts/new-project.sh');
    expect(file_exists($scriptPath))->toBeTrue();
    expect(is_executable($scriptPath))->toBeTrue();
});
