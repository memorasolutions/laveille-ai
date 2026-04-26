<?php
declare(strict_types=1);

use Modules\Directory\Models\Tool;

test('Tool has pricingDistribution static method', function () {
    expect(method_exists(Tool::class, 'pricingDistribution'))->toBeTrue();
});

test('Tool pricingDistribution method is static', function () {
    $reflection = new ReflectionMethod(Tool::class, 'pricingDistribution');
    expect($reflection->isStatic())->toBeTrue();
});

test('Tool pricingDistribution return type is array', function () {
    $reflection = new ReflectionMethod(Tool::class, 'pricingDistribution');
    expect($reflection->getReturnType()?->getName())->toBe('array');
});

test('Tool pricingDistribution source uses notArchived scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('pricingDistribution');
    expect($source)->toContain('->notArchived()');
});

test('Tool pricingDistribution source uses selectRaw with pricing count', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('selectRaw');
    expect($source)->toContain('pricing, count');
});

test('Tool pricingDistribution source groups by pricing', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain("groupBy('pricing')");
});

test('Tool pricingDistribution source uses pluck cnt pricing toArray', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain("pluck('cnt', 'pricing')");
    expect($source)->toContain('toArray');
});
