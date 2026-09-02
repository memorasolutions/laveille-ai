<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\Support\ParallelSafety;

beforeEach(function (): void {
    // Ces tests mutent le fichier PARTAGÉ modules_statuses.json (base_path, pas une copie).
    // Le POURQUOI complet (course reproduite, absence de verrou Laravel) vit dans
    // Tests\Support\ParallelSafety - une seule source, pour que les trois fichiers
    // concernés ne puissent pas diverger sur une règle de sécurité.
    // Le sauvegarder et le restaurer ne protège que CE processus : un autre processus qui
    // boote pendant la fenêtre de mutation lit un instantané tronqué et échoue, avec un
    // message qui varie selon la clé absente au moment exact (« No hint path defined for
    // [fronttheme] », « Module [AI] requires [Settings] »...). Course reproduite le
    // 2026-09-02 en faisant tourner ce fichier et BlogAdminTest dans deux processus
    // concurrents : échec après ~11 itérations. Aucun verrou dans la chaîne Laravel
    // (Filesystem::put/get ont $lock = false), et FileActivator ne lit le fichier
    // qu'UNE fois, au boot.
    //
    // Même garde que Modules/Core/tests/Feature/ModuleIsolationTest.php, qui mute le
    // même fichier et se protège déjà ainsi. Détection large : les quatre variables
    // couvrent paratest et le mode parallèle natif de Laravel.
    // ORDRE IMPORTANT : initialiser AVANT de sauter. Le afterEach s'exécute même sur un
    // test sauté ; s'il ne trouve pas $this->originalModulesStatus, les 8 tests échouent
    // au lieu d'être ignorés (mesuré le 2026-09-02 en écrivant ce garde-fou).
    $this->modulesStatusPath = base_path('modules_statuses.json');
    $this->originalModulesStatus = File::exists($this->modulesStatusPath)
        ? File::get($this->modulesStatusPath)
        : null;

    if (ParallelSafety::isParallel()) {
        $this->markTestSkipped(ParallelSafety::sharedFileSkipReason());
    }
});

afterEach(function (): void {
    if ($this->originalModulesStatus !== null) {
        File::put($this->modulesStatusPath, $this->originalModulesStatus);
    }
});

test('core:prune command exists', function () {
    $this->artisan('core:prune', ['preset' => 'nonexistent'])
        ->assertFailed();
});

test('core:prune fails with unknown preset', function () {
    $this->artisan('core:prune', ['preset' => 'does_not_exist'])
        ->expectsOutput("Preset 'does_not_exist' not found in config/presets.php.")
        ->assertExitCode(1);
});

test('core:prune saas preset disables correct modules', function () {
    Config::set('presets.test_saas', [
        'description' => 'Test SaaS',
        'modules_disabled' => ['Blog', 'Faq'],
        'env_overrides' => [],
    ]);

    File::put($this->modulesStatusPath, json_encode([
        'Core' => true,
        'Auth' => true,
        'Blog' => true,
        'Faq' => true,
    ]));

    $this->artisan('core:prune', ['preset' => 'test_saas'])
        ->expectsConfirmation('Apply these changes?', 'yes')
        ->assertSuccessful();

    $statuses = json_decode(File::get($this->modulesStatusPath), true);

    expect($statuses['Blog'])->toBeFalse();
    expect($statuses['Faq'])->toBeFalse();
    expect($statuses['Core'])->toBeTrue();
    expect($statuses['Auth'])->toBeTrue();
});

test('core:prune foundation modules cannot be disabled', function () {
    $foundation = ['Core', 'Auth', 'Backoffice', 'Settings', 'RolesPermissions',
        'Notifications', 'Logging', 'Health', 'Media', 'Editor', 'Privacy', 'Storage', 'Backup'];

    Config::set('presets.aggressive', [
        'description' => 'Aggressive prune',
        'modules_disabled' => $foundation,
        'env_overrides' => [],
    ]);

    $initial = array_fill_keys($foundation, true);
    File::put($this->modulesStatusPath, json_encode($initial));

    $this->artisan('core:prune', ['preset' => 'aggressive'])
        ->expectsConfirmation('Apply these changes?', 'yes')
        ->assertSuccessful();

    $statuses = json_decode(File::get($this->modulesStatusPath), true);

    foreach ($foundation as $module) {
        expect($statuses[$module])->toBeTrue("Foundation module {$module} should stay enabled");
    }
});

test('core:prune cancellation does not modify files', function () {
    Config::set('presets.cancel_test', [
        'description' => 'Cancel test',
        'modules_disabled' => ['Blog'],
        'env_overrides' => [],
    ]);

    $initial = json_encode(['Core' => true, 'Blog' => true]);
    File::put($this->modulesStatusPath, $initial);

    $this->artisan('core:prune', ['preset' => 'cancel_test'])
        ->expectsConfirmation('Apply these changes?', 'no')
        ->assertSuccessful();

    expect(File::get($this->modulesStatusPath))->toBe($initial);
});

test('core:prune handles missing modules_statuses.json', function () {
    if (File::exists($this->modulesStatusPath)) {
        File::delete($this->modulesStatusPath);
    }

    Config::set('presets.empty_test', [
        'description' => 'Empty test',
        'modules_disabled' => [],
        'env_overrides' => [],
    ]);

    $this->artisan('core:prune', ['preset' => 'empty_test'])
        ->expectsConfirmation('Apply these changes?', 'yes')
        ->assertSuccessful();

    expect(File::exists($this->modulesStatusPath))->toBeTrue();
});

test('core:prune real presets are defined in config', function () {
    expect(config('presets.saas'))->not->toBeNull();
    expect(config('presets.blog'))->not->toBeNull();
    expect(config('presets.minimal'))->not->toBeNull();

    expect(config('presets.saas.modules_disabled'))->toBeArray();
    expect(config('presets.blog.modules_disabled'))->toBeArray();
    expect(config('presets.minimal.modules_disabled'))->toBeArray();
});

test('core:prune interactive mode requires preset selection', function () {
    $this->artisan('core:prune')
        ->expectsOutput('Preset name required. Usage: core:prune {saas|blog|minimal} or --interactive')
        ->assertExitCode(1);
});
