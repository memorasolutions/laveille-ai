<?php
declare(strict_types=1);

test('DirectoryAdminController imports ToolPricingReport', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('use Modules\Directory\Models\ToolPricingReport;');
});

test('DirectoryAdminController pricingDrift counts auto-flagged pending', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('ToolPricingReport::pending()->autoFlagged()->count()');
});

test('DirectoryAdminController pricingDrift counts user-submitted pending', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('ToolPricingReport::pending()->userSubmitted()->count()');
});

test('DirectoryAdminController pricingDrift compact includes review queue counts', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("'autoFlaggedPending'");
    expect($source)->toContain("'userSubmittedPending'");
});

test('pricing-drift Blade displays auto-flag count', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('$autoFlaggedPending');
});

test('pricing-drift Blade displays user-submitted count', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('$userSubmittedPending');
});

test('pricing-drift Blade links to admin pricing-reports', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain("route('admin.directory.pricing-reports')");
});

test('pricing-drift Blade guards counters when both zero', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('autoFlaggedPending ?? 0');
    expect($source)->toContain('userSubmittedPending ?? 0');
});
