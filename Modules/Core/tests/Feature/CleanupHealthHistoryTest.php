<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->commandFile = base_path('Modules/Core/app/Console/CleanupOldRecords.php');
    $this->commandClass = 'Modules\\Core\\Console\\CleanupOldRecords';
});

it('verifies the CleanupOldRecords command file exists', function () {
    expect($this->commandFile)->toBeFile();
});

it('checks that source contains retention.health_check_history_days setting key', function () {
    $content = file_get_contents($this->commandFile);
    expect($content)->toContain('retention.health_check_history_days');
});

it('checks that source contains health_check_result_history_items table name', function () {
    $content = file_get_contents($this->commandFile);
    expect($content)->toContain('health_check_result_history_items');
});

it('checks that source contains chunkSize: 5000', function () {
    $content = file_get_contents($this->commandFile);
    expect($content)->toContain('chunkSize: 5000');
});

it('validates cleanTable private method signature', function () {
    $reflection = new ReflectionClass($this->commandClass);
    $method = $reflection->getMethod('cleanTable');
    expect($method->isPrivate())->toBeTrue();

    $parameters = $method->getParameters();
    expect(count($parameters))->toBe(5);

    expect($parameters[0]->getName())->toBe('table');
    expect($parameters[0]->getType()?->getName())->toBe('string');

    expect($parameters[1]->getName())->toBe('column');
    expect($parameters[1]->getType()?->getName())->toBe('string');

    expect($parameters[2]->getName())->toBe('days');
    expect($parameters[2]->getType()?->getName())->toBe('int');

    expect($parameters[3]->getName())->toBe('dryRun');
    expect($parameters[3]->getType()?->getName())->toBe('bool');

    expect($parameters[4]->getName())->toBe('chunkSize');
    expect($parameters[4]->getType()?->getName())->toBe('int');
    expect($parameters[4]->isDefaultValueAvailable())->toBeTrue();
    expect($parameters[4]->getDefaultValue())->toBe(0);
});

it('validates handle public method returns int and signature unchanged', function () {
    $reflection = new ReflectionClass($this->commandClass);
    $method = $reflection->getMethod('handle');

    expect($method->isPublic())->toBeTrue();
    expect($method->getReturnType()?->getName())->toBe('int');

    $parameters = $method->getParameters();
    expect(count($parameters))->toBe(0);
});
