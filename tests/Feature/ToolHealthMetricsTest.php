<?php
declare(strict_types=1);

use Modules\Directory\Models\Tool;

test('Tool has healthMetrics static method', function () {
    expect(method_exists(Tool::class, 'healthMetrics'))->toBeTrue();
});

test('Tool healthMetrics method is static', function () {
    $reflection = new ReflectionMethod(Tool::class, 'healthMetrics');
    expect($reflection->isStatic())->toBeTrue();
});

test('Tool healthMetrics return type is array', function () {
    $reflection = new ReflectionMethod(Tool::class, 'healthMetrics');
    expect($reflection->getReturnType()?->getName())->toBe('array');
});

test('Tool healthMetrics source uses pricingDistribution and countByStatus', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('healthMetrics');
    expect($source)->toContain('self::pricingDistribution()');
    expect($source)->toContain('self::countByStatus()');
});

test('Tool healthMetrics source uses driftCount and neverCheckedCount', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('self::driftCount(90)');
    expect($source)->toContain('self::driftCount(180)');
    expect($source)->toContain('self::neverCheckedCount()');
});

test('Tool healthMetrics source uses ToolPricingReport pendingCount', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain('ToolPricingReport::pendingCount()');
});

test('Tool healthMetrics source contains expected keys', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Models/Tool.php'));
    expect($source)->toContain("'distribution'");
    expect($source)->toContain("'status'");
    expect($source)->toContain("'drift_90'");
    expect($source)->toContain("'drift_180'");
    expect($source)->toContain("'never_checked'");
    expect($source)->toContain("'pending_reports'");
});
