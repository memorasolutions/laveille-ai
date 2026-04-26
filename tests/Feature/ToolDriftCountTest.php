<?php
declare(strict_types=1);

use Modules\Directory\Models\Tool;

test('Tool has driftCount static method', function () {
    expect(method_exists(Tool::class, 'driftCount'))->toBeTrue();
});

test('Tool driftCount method is static', function () {
    $reflection = new ReflectionMethod(Tool::class, 'driftCount');
    expect($reflection->isStatic())->toBeTrue();
});

test('Tool driftCount return type is int', function () {
    $reflection = new ReflectionMethod(Tool::class, 'driftCount');
    expect($reflection->getReturnType()?->getName())->toBe('int');
});

test('Tool driftCount accepts days parameter with default 90', function () {
    $reflection = new ReflectionMethod(Tool::class, 'driftCount');
    $params = $reflection->getParameters();
    expect(count($params))->toBe(1);
    expect($params[0]->getName())->toBe('days');
    expect($params[0]->getDefaultValue())->toBe(90);
});

test('Tool driftCount source uses notArchived scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('driftCount');
    expect($source)->toContain('->notArchived()');
});
