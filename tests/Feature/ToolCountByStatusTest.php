<?php
declare(strict_types=1);

use Modules\Directory\Models\Tool;

test('Tool has countByStatus static method', function () {
    expect(method_exists(Tool::class, 'countByStatus'))->toBeTrue();
});

test('Tool countByStatus method is static', function () {
    $reflection = new ReflectionMethod(Tool::class, 'countByStatus');
    expect($reflection->isStatic())->toBeTrue();
});

test('Tool countByStatus return type is array', function () {
    $reflection = new ReflectionMethod(Tool::class, 'countByStatus');
    expect($reflection->getReturnType()?->getName())->toBe('array');
});

test('Tool countByStatus source uses selectRaw status count', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('countByStatus');
    expect($source)->toContain('selectRaw');
    expect($source)->toContain('status, count(*)');
});

test('Tool countByStatus source groups by status and plucks cnt status', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain("groupBy('status')");
    expect($source)->toContain("pluck('cnt', 'status')");
});
