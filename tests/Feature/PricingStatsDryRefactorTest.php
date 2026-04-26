<?php
declare(strict_types=1);

test('PricingStatsCommand uses Tool driftCount helper', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('Tool::driftCount(90)');
});

test('PricingStatsCommand uses Tool neverCheckedCount helper', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('Tool::neverCheckedCount()');
});

test('PricingStatsCommand no longer hardcodes notArchived where last_enriched_at orWhereNull', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->not->toContain("Tool::published()->notArchived()->where(fn (\$q) => \$q->where('last_enriched_at', '<', \$cutoff90)");
});

test('PricingStatsCommand no longer hardcodes whereNull last_enriched_at count', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->not->toContain("Tool::published()->notArchived()->whereNull('last_enriched_at')->count()");
});

test('PricingStatsCommand keeps autoFlagged and userSubmitted granular scopes', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('->autoFlagged()');
    expect($source)->toContain('->userSubmitted()');
});
