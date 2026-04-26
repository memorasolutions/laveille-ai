<?php
declare(strict_types=1);

use Modules\Directory\Models\ToolPricingReport;

test('ToolPricingReport has pendingCount static method', function () {
    expect(method_exists(ToolPricingReport::class, 'pendingCount'))->toBeTrue();
});

test('ToolPricingReport pendingCount method is static', function () {
    $reflection = new ReflectionMethod(ToolPricingReport::class, 'pendingCount');
    expect($reflection->isStatic())->toBeTrue();
});

test('ToolPricingReport pendingCount return type is int', function () {
    $reflection = new ReflectionMethod(ToolPricingReport::class, 'pendingCount');
    expect($reflection->getReturnType()?->getName())->toBe('int');
});

test('ToolPricingReport pendingCount source uses pending scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/ToolPricingReport.php'));
    expect($source)->toContain('pendingCount');
    expect($source)->toContain('self::pending()');
});
