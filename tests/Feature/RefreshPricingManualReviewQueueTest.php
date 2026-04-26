<?php
declare(strict_types=1);

test('RefreshPricingCommand source creates ToolPricingReport when low confidence', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('ToolPricingReport::create');
});

test('RefreshPricingCommand low confidence report uses pending status', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("'status' => 'pending'");
});

test('RefreshPricingCommand low confidence report tags Auto-flagged user_notes', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('Auto-flagged low confidence');
});

test('RefreshPricingCommand low confidence report carries evidence in admin_notes', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("'admin_notes' => 'Evidence: ' . \$evidence");
});

test('RefreshPricingCommand low confidence report user_id is null for system origin', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain("'user_id' => null");
});

test('RefreshPricingCommand low confidence still increments lowConfidence counter', function () {
    $source = file_get_contents(base_path('Modules/Directory/app/Console/RefreshPricingCommand.php'));
    expect($source)->toContain('$lowConfidence++');
});
