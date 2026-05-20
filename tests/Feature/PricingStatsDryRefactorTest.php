<?php
declare(strict_types=1);

test('PricingStatsCommand surfaces drift metric via healthMetrics', function () {
    // Refactor : la dérive provient désormais de Tool::healthMetrics()['drift_90']
    // (consolide l'ancien helper Tool::driftCount(90)).
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain('Tool::healthMetrics()');
    expect($source)->toContain("['drift_90']");
});

test('PricingStatsCommand surfaces never-checked metric via healthMetrics', function () {
    // Refactor : never_checked provient désormais de Tool::healthMetrics()['never_checked']
    // (consolide l'ancien helper Tool::neverCheckedCount()).
    $source = file_get_contents(base_path('Modules/Directory/app/Console/PricingStatsCommand.php'));
    expect($source)->toContain("['never_checked']");
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
