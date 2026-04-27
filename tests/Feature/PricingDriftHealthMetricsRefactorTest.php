<?php
declare(strict_types=1);

test('pricingDrift uses Tool healthMetrics facade', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('Tool::healthMetrics()');
});

test('pricingDrift accesses drift_90 from healthMetrics', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("['drift_90']");
});

test('pricingDrift accesses never_checked from healthMetrics', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("['never_checked']");
});

test('pricingDrift accesses drift_180 from healthMetrics', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("['drift_180']");
});

test('pricingDrift accesses distribution from healthMetrics', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("['distribution']");
});

test('pricingDrift no longer uses old Tool method calls', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->not->toContain('Tool::driftCount(90)')
        ->and($source)->not->toContain('Tool::neverCheckedCount()')
        ->and($source)->not->toContain('Tool::driftCount(180)')
        ->and($source)->not->toContain('Tool::pricingDistribution()');
});
