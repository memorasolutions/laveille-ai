<?php
declare(strict_types=1);

test('RefreshPricingCommand source contains orderByRaw', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('orderByRaw');
});

test('RefreshPricingCommand source contains FIELD function with pricing column', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("FIELD(pricing,");
});

test('RefreshPricingCommand priorizes volatile pricings first', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("'freemium', 'free_trial', 'paid', 'free', 'open_source', 'enterprise'");
});

test('RefreshPricingCommand keeps orderBy updated_at as secondary ordering', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("orderBy('updated_at')");
});

test('RefreshPricingCommand normal branch chains notArchived and orderByRaw', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    $hasOrder = preg_match('/notArchived\(\)\s*->\s*orderByRaw\("FIELD\(pricing/', $source);
    expect($hasOrder)->toBe(1);
});
