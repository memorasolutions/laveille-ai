<?php
declare(strict_types=1);

use Modules\Directory\Models\Tool;

test('Tool has neverCheckedCount static method', function () {
    expect(method_exists(Tool::class, 'neverCheckedCount'))->toBeTrue();
});

test('Tool neverCheckedCount method is static', function () {
    $reflection = new ReflectionMethod(Tool::class, 'neverCheckedCount');
    expect($reflection->isStatic())->toBeTrue();
});

test('Tool neverCheckedCount return type is int', function () {
    $reflection = new ReflectionMethod(Tool::class, 'neverCheckedCount');
    expect($reflection->getReturnType()?->getName())->toBe('int');
});

test('Tool neverCheckedCount source uses whereNull last_enriched_at', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('neverCheckedCount');
    expect($source)->toContain("whereNull('last_enriched_at')");
});
