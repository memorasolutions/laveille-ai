<?php

declare(strict_types=1);

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
        'Backoffice', 'FrontTheme',
    ];

    foreach ($expectedModules as $module) {
        expect($enabledNames)->toContain($module);
    }
});

test('saas and tenancy modules are disabled', function () {
    $disabled = app('modules')->allDisabled();
    $disabledNames = collect($disabled)->map(fn ($m) => $m->getName())->toArray();
    expect($disabledNames)->toContain('SaaS');
    expect($disabledNames)->toContain('Tenancy');
});

test('admin panel provider is registered', function () {
    expect(class_exists(\Modules\Backoffice\Providers\AdminPanelProvider::class))->toBeTrue();
});

test('database seeder can run', function () {
    $this->artisan('migrate:fresh');
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $admin = \App\Models\User::where('email', 'admin@laravel-core.test')->first();
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
