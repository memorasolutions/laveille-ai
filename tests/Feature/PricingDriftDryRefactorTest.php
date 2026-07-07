<?php
declare(strict_types=1);

test('DirectoryAdminController pricingDrift uses Tool healthMetrics aggregate helper', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('Tool::healthMetrics()');
    expect($source)->toContain("\$healthMetrics['drift_90']");
    expect($source)->toContain("\$healthMetrics['drift_180']");
});

test('DirectoryAdminController pricingDrift uses Tool healthMetrics for never checked count', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("\$healthMetrics['never_checked']");
});

test('DirectoryAdminController pricingDrift no hardcoded count of last_enriched_at', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->not->toContain('Tool::published()->whereNull(\'last_enriched_at\')->count()');
    expect($source)->not->toContain('Tool::published()->where(\'last_enriched_at\', \'<\', $cutoff180)->count()');
});

test('DirectoryAdminController pricingDrift query uses notArchived scope', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('Tool::published()->notArchived()');
});

test('DirectoryAdminController pricingDrift compact still includes 4 metric variables', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("'totalDrifted'");
    expect($source)->toContain("'neverChecked'");
    expect($source)->toContain("'criticalDrift'");
    expect($source)->toContain("'distribution'");
});
