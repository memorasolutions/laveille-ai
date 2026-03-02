<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Backup and restore .env to prevent side effects
beforeEach(function () {
    $this->envPath = base_path('.env');
    $this->envBackup = file_get_contents($this->envPath);
});

afterEach(function () {
    file_put_contents($this->envPath, $this->envBackup);
});

function buildCommand(\Illuminate\Testing\PendingCommand $command, array $businessAnswers = [], array $advancedAnswers = []): \Illuminate\Testing\PendingCommand
{
    $businessModules = ['blog', 'newsletter', 'faq', 'testimonials', 'widget', 'formbuilder', 'customfields'];
    $advancedModules = ['ai', 'team', 'saas', 'tenancy', 'abtest', 'import', 'sms'];

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
        ->toHaveCount(20);

    expect($business)->toBeArray()
        ->toHaveKeys(['blog', 'newsletter', 'faq', 'testimonials', 'widget', 'formbuilder', 'customfields'])
        ->toHaveCount(7);

    expect($advanced)->toBeArray()
        ->toHaveKeys(['ai', 'team', 'saas', 'tenancy', 'abtest', 'import', 'sms'])
        ->toHaveCount(7);
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
    ]);

    $command->assertSuccessful();
});
