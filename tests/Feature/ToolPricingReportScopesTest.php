<?php
declare(strict_types=1);

use Modules\Directory\Models\ToolPricingReport;

test('ToolPricingReport has scopeAutoFlagged method', function () {
    expect(method_exists(ToolPricingReport::class, 'scopeAutoFlagged'))->toBeTrue();
});

test('ToolPricingReport has scopeUserSubmitted method', function () {
    expect(method_exists(ToolPricingReport::class, 'scopeUserSubmitted'))->toBeTrue();
});

test('scopeAutoFlagged produces SQL with user_id IS NULL', function () {
    $sql = ToolPricingReport::query()->autoFlagged()->toSql();
    expect($sql)->toContain('user_id');
    expect($sql)->toContain('is null');
});

test('scopeUserSubmitted produces SQL with user_id IS NOT NULL', function () {
    $sql = ToolPricingReport::query()->userSubmitted()->toSql();
    expect($sql)->toContain('user_id');
    expect($sql)->toContain('is not null');
});

test('scopeAutoFlagged can chain with scopePending', function () {
    $sql = ToolPricingReport::pending()->autoFlagged()->toSql();
    expect($sql)->toContain('status');
    expect($sql)->toContain('user_id');
});

test('scopeUserSubmitted can chain with scopeReviewed', function () {
    $sql = ToolPricingReport::reviewed()->userSubmitted()->toSql();
    expect($sql)->toContain('status');
    expect($sql)->toContain('user_id');
});
