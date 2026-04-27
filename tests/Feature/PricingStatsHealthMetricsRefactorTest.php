<?php
declare(strict_types=1);

test('PricingStatsCommand uses Tool healthMetrics facade', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('Tool::healthMetrics()');
});

test('PricingStatsCommand extracts distribution from metrics array', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain("\$metrics['distribution']");
});

test('PricingStatsCommand extracts drift_90 from metrics array', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain("\$metrics['drift_90']");
});

test('PricingStatsCommand extracts never_checked from metrics array', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain("\$metrics['never_checked']");
});

test('PricingStatsCommand no longer calls driftCount neverCheckedCount pricingDistribution directly', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->not->toContain('Tool::driftCount(90)');
    expect($source)->not->toContain('Tool::neverCheckedCount()');
    expect($source)->not->toContain('Tool::pricingDistribution()');
});
