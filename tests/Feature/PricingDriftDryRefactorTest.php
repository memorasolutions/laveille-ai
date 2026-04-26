<?php
declare(strict_types=1);

test('DirectoryAdminController pricingDrift uses Tool driftCount helper', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('Tool::driftCount(90)');
    expect($source)->toContain('Tool::driftCount(180)');
});

test('DirectoryAdminController pricingDrift uses Tool neverCheckedCount helper', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('Tool::neverCheckedCount()');
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
