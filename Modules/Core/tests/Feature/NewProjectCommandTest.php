<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Backup and restore .env to prevent side effects
beforeEach(function () {
    $this->envPath = base_path('.env');
    $this->envBackup = file_get_contents($this->envPath);
    $this->modulesPath = base_path('modules_statuses.json');
    $this->modulesBackup = file_get_contents($this->modulesPath);
});

afterEach(function () {
    file_put_contents($this->envPath, $this->envBackup);
    file_put_contents($this->modulesPath, $this->modulesBackup);
});

function buildCommand(\Illuminate\Testing\PendingCommand $command, array $businessAnswers = [], array $advancedAnswers = []): \Illuminate\Testing\PendingCommand
{
    $businessModules = ['blog', 'newsletter', 'faq', 'testimonials', 'widget', 'formbuilder', 'customfields', 'shorturl'];
    $advancedModules = ['ai', 'team', 'saas', 'tenancy', 'abtest', 'import', 'api', 'booking', 'roadmap'];

    foreach ($businessModules as $module) {
        $answer = $businessAnswers[$module] ?? 'yes';
        $command = $command->expectsConfirmation("  Activer le module {$module} ?", $answer);
    }

    foreach ($advancedModules as $module) {
        $answer = $advancedAnswers[$module] ?? 'no';
        $command = $command->expectsConfirmation("  Activer le module {$module} ?", $answer);
    }

    return $command;
}

test('core:new-project command is registered with categorized modules', function () {
    expect(class_exists(\Modules\Core\Console\NewProjectCommand::class))->toBeTrue();
});

test('core:new-project has correct module constants', function () {
    $reflection = new ReflectionClass(\Modules\Core\Console\NewProjectCommand::class);

    $core = $reflection->getConstant('CORE_MODULES');
    $business = $reflection->getConstant('BUSINESS_MODULES');
    $advanced = $reflection->getConstant('ADVANCED_MODULES');

    expect($core)->toBeArray()
        ->toContain('Auth', 'Core', 'Settings', 'RolesPermissions', 'Backoffice')
        ->toHaveCount(19);

    expect($business)->toBeArray()
        ->toHaveKeys(['blog', 'newsletter', 'faq', 'testimonials', 'widget', 'formbuilder', 'customfields', 'shorturl'])
        ->toHaveCount(8);

    expect($advanced)->toBeArray()
        ->toHaveKeys(['ai', 'team', 'saas', 'tenancy', 'abtest', 'import', 'api', 'booking', 'roadmap'])
        ->toHaveCount(9);
});

test('core:new-project business modules default to true', function () {
    $reflection = new ReflectionClass(\Modules\Core\Console\NewProjectCommand::class);
    $business = $reflection->getConstant('BUSINESS_MODULES');

    foreach ($business as $name => $default) {
        expect($default)->toBeTrue("Business module {$name} should default to true");
    }
});

test('core:new-project advanced modules default to false', function () {
    $reflection = new ReflectionClass(\Modules\Core\Console\NewProjectCommand::class);
    $advanced = $reflection->getConstant('ADVANCED_MODULES');

    foreach ($advanced as $name => $default) {
        expect($default)->toBeFalse("Advanced module {$name} should default to false");
    }
});

test('core:new-project succeeds with all defaults', function () {
    $command = $this->artisan('core:new-project')
        ->expectsQuestion("Nom de l'application", 'Test App')
        ->expectsQuestion("URL de l'application", 'http://localhost')
        ->expectsQuestion('Nom de la base de données', 'test_db');

    $command = buildCommand($command);

    $command->assertSuccessful();
});

test('core:new-project succeeds with advanced modules enabled', function () {
    $command = $this->artisan('core:new-project')
        ->expectsQuestion("Nom de l'application", 'My SaaS')
        ->expectsQuestion("URL de l'application", 'https://mysaas.com')
        ->expectsQuestion('Nom de la base de données', 'my_saas');

    $command = buildCommand($command, [], [
        'ai' => 'yes',
        'team' => 'yes',
        'saas' => 'yes',
    ]);

    $command->assertSuccessful();
});

test('core:new-project succeeds with business modules disabled', function () {
    $command = $this->artisan('core:new-project')
        ->expectsQuestion("Nom de l'application", 'Minimal App')
        ->expectsQuestion("URL de l'application", 'http://localhost')
        ->expectsQuestion('Nom de la base de données', 'minimal');

    $command = buildCommand($command, [
        'blog' => 'no',
        'newsletter' => 'no',
        'faq' => 'no',
        'testimonials' => 'no',
        'widget' => 'no',
        'formbuilder' => 'no',
        'customfields' => 'no',
        'shorturl' => 'no',
    ]);

    $command->assertSuccessful();
});

test('core:new-project has toggleNwidartModules method', function () {
    $reflection = new ReflectionClass(\Modules\Core\Console\NewProjectCommand::class);

    expect($reflection->hasMethod('toggleNwidartModules'))->toBeTrue();

    $method = $reflection->getMethod('toggleNwidartModules');
    expect($method->isPrivate())->toBeTrue();

    $params = $method->getParameters();
    expect($params)->toHaveCount(1);
    expect($params[0]->getName())->toBe('allSelected');
});

test('core:new-project has alias to module mapping for all optional modules', function () {
    $reflection = new ReflectionClass(\Modules\Core\Console\NewProjectCommand::class);
    $mapping = $reflection->getConstant('ALIAS_TO_MODULE');

    $business = $reflection->getConstant('BUSINESS_MODULES');
    $advanced = $reflection->getConstant('ADVANCED_MODULES');
    $allOptional = array_merge(array_keys($business), array_keys($advanced));

    foreach ($allOptional as $alias) {
        expect($mapping)->toHaveKey($alias);
    }

    expect($mapping)->toHaveCount(count($allOptional));
});
