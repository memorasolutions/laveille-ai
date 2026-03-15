<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Nwidart\Modules\Facades\Module;

uses(Tests\TestCase::class);

dataset('optional_modules', [
    'Blog',
    'Newsletter',
    'Faq',
    'Testimonials',
    'Widget',
    'FormBuilder',
    'CustomFields',
    'ShortUrl',
    'AI',
    'Team',
    'SaaS',
    'Tenancy',
    'ABTest',
    'Import',
    'Api',
    'Booking',
    'Roadmap',
    'Ecommerce',
]);

test('route:list does not crash when an optional module is disabled', function (string $module) {
    if (env('LARAVEL_PARALLEL_TESTING')) {
        $this->markTestSkipped('Modifies shared modules_statuses.json — unsafe in parallel.');
    }

    $statusPath = base_path('modules_statuses.json');
    $backup = file_get_contents($statusPath);

    try {
        Module::disable($module);

        $this->artisan('route:list')
            ->assertExitCode(0);
    } finally {
        file_put_contents($statusPath, $backup);
    }
})->with('optional_modules');

test('all 38 modules have a valid plugin.json file', function () {
    $modules = Module::all();

    expect($modules)->toHaveCount(38);

    foreach ($modules as $module) {
        $pluginPath = $module->getPath().'/plugin.json';

        expect(file_exists($pluginPath))->toBeTrue("Missing plugin.json for {$module->getName()}");

        $decoded = json_decode(file_get_contents($pluginPath), true);

        expect($decoded)->not->toBeNull("Invalid JSON in plugin.json for {$module->getName()}");
        expect($decoded)->toHaveKey('name');
    }
});
