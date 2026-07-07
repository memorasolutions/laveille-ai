<?php
declare(strict_types=1);

test('DirectoryAdminController pricingDrift uses Tool healthMetrics distribution', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain('Tool::healthMetrics()');
    expect($source)->toContain("\$healthMetrics['distribution']");
});

test('DirectoryAdminController pricingDrift compact contains distribution', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/Admin/DirectoryAdminController.php'));
    expect($source)->toContain("'distribution'");
});

test('pricing-drift Blade uses distribution variable', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('$distribution');
});

test('pricing-drift Blade iterates distribution with foreach', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('@foreach($distribution');
});

test('pricing-drift Blade displays distribution title', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('Distribution tarification');
});

test('pricing-drift Blade guards empty distribution', function () {
    $source = file_get_contents(base_path('Modules/Directory/resources/views/admin/pricing-drift.blade.php'));
    expect($source)->toContain('@if(!empty($distribution))');
});
