<?php

declare(strict_types=1);

use Modules\Directory\Http\Controllers\Admin\DirectoryAdminController;
use Modules\Directory\Http\Controllers\Admin\ModerationController;
use Modules\Directory\Http\Controllers\PublicDirectoryController;
use Modules\Directory\Models\ToolPricingReport;

test('ToolPricingReport model exists', function () {
    expect(class_exists(ToolPricingReport::class))->toBeTrue();
});

test('ToolPricingReport has tool relation', function () {
    expect(method_exists(ToolPricingReport::class, 'tool'))->toBeTrue();
});

test('ToolPricingReport has user relation', function () {
    expect(method_exists(ToolPricingReport::class, 'user'))->toBeTrue();
});

test('ToolPricingReport has reviewer relation', function () {
    expect(method_exists(ToolPricingReport::class, 'reviewer'))->toBeTrue();
});

test('ToolPricingReport has scopePending', function () {
    expect(method_exists(ToolPricingReport::class, 'scopePending'))->toBeTrue();
});

test('ToolPricingReport has scopeReviewed', function () {
    expect(method_exists(ToolPricingReport::class, 'scopeReviewed'))->toBeTrue();
});

test('ToolPricingReport has scopeForTool', function () {
    expect(method_exists(ToolPricingReport::class, 'scopeForTool'))->toBeTrue();
});

test('ToolPricingReport fillable attributes include expected fields', function () {
    $fillable = (new ToolPricingReport())->getFillable();
    $expected = [
        'tool_id',
        'user_id',
        'reported_pricing',
        'current_pricing_snapshot',
        'evidence_url',
        'user_notes',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];
    foreach ($expected as $field) {
        expect($fillable)->toContain($field);
    }
});

test('ToolPricingReport casts reviewed_at as datetime', function () {
    $casts = (new ToolPricingReport())->getCasts();
    expect($casts)->toHaveKey('reviewed_at', 'datetime');
});

test('PublicDirectoryController has storePricingReport method', function () {
    expect(method_exists(PublicDirectoryController::class, 'storePricingReport'))->toBeTrue();
});

test('DirectoryAdminController has pricingDrift method', function () {
    expect(method_exists(DirectoryAdminController::class, 'pricingDrift'))->toBeTrue();
});

test('ModerationController has pricingReports method', function () {
    expect(method_exists(ModerationController::class, 'pricingReports'))->toBeTrue();
});

test('ModerationController has approvePricingReport method', function () {
    expect(method_exists(ModerationController::class, 'approvePricingReport'))->toBeTrue();
});

test('ModerationController has rejectPricingReport method', function () {
    expect(method_exists(ModerationController::class, 'rejectPricingReport'))->toBeTrue();
});

test('route directory.pricing-report registered', function () {
    expect(\Route::has('directory.pricing-report'))->toBeTrue();
});

test('route admin.directory.pricing-reports registered', function () {
    expect(\Route::has('admin.directory.pricing-reports'))->toBeTrue();
});

test('route admin.directory.pricing-reports.approve registered', function () {
    expect(\Route::has('admin.directory.pricing-reports.approve'))->toBeTrue();
});

test('route admin.directory.pricing-reports.reject registered', function () {
    expect(\Route::has('admin.directory.pricing-reports.reject'))->toBeTrue();
});

test('route admin.directory.pricing-drift registered', function () {
    expect(\Route::has('admin.directory.pricing-drift'))->toBeTrue();
});
