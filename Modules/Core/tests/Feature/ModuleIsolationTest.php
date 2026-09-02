<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Nwidart\Modules\Facades\Module;
use Tests\Support\ParallelSafety;

uses(Tests\TestCase::class);

// Modules optionnels réellement présents dans ce déploiement (Ecommerce retiré : n'existe pas ici).
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
]);

test('route:list does not crash when an optional module is disabled', function (string $module) {
    // Ce test mute le fichier partagé modules_statuses.json : les autres processus
    // liraient un module désactivé à mi-course. Le POURQUOI détaillé et la détection
    // elle-même vivent dans Tests\Support\ParallelSafety (source unique).
    if (ParallelSafety::isParallel()) {
        $this->markTestSkipped(ParallelSafety::sharedFileSkipReason());
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

test('every module plugin.json present is valid JSON with a name', function () {
    $modules = Module::all();

    // Le nombre de modules évolue (≠ valeur figée). On vérifie un plancher socle.
    expect(count($modules))->toBeGreaterThanOrEqual(38);

    // plugin.json est le manifeste du pattern « plugin exportable ». Les modules socle (boilerplate)
    // en disposent ; certains modules spécifiques au projet (Directory, News, Tools, etc.) n'en ont pas
    // encore. On valide donc le contenu UNIQUEMENT pour les modules qui en fournissent un.
    $withPlugin = 0;
    foreach ($modules as $module) {
        $pluginPath = $module->getPath().'/plugin.json';

        if (! file_exists($pluginPath)) {
            continue;
        }

        $withPlugin++;
        $decoded = json_decode(file_get_contents($pluginPath), true);

        expect($decoded)->not->toBeNull("Invalid JSON in plugin.json for {$module->getName()}");
        expect($decoded)->toHaveKey('name');
    }

    // Au moins le socle de modules boilerplate doit fournir un plugin.json valide.
    expect($withPlugin)->toBeGreaterThanOrEqual(30);
});
