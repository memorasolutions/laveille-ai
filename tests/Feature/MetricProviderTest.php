<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\DataTransferObjects\MetricWidget;
use Modules\Core\Services\MetricAggregatorService;

uses(RefreshDatabase::class);

test('MetricWidget creates with all properties', function () {
    $widget = new MetricWidget(
        name: 'Revenue',
        value: '1,234.56',
        type: 'currency',
        change: '+12%',
        icon: 'dollar-sign',
        route: '/admin/analytics',
    );

    expect($widget->name)->toBe('Revenue')
        ->and($widget->type)->toBe('currency')
        ->and($widget->toArray())->toHaveKeys(['name', 'value', 'type', 'change', 'icon', 'route']);
});

test('MetricWidget toArray filters null values', function () {
    $widget = new MetricWidget(name: 'Count', value: '42');

    $array = $widget->toArray();

    expect($array)->toHaveKeys(['name', 'value', 'type'])
        ->not->toHaveKey('change')
        ->not->toHaveKey('icon');
});

test('MetricAggregatorService getAllWidgets collects from all providers', function () {
    $aggregator = app(MetricAggregatorService::class);
    $widgets = $aggregator->getAllWidgets();

    expect($widgets)->toBeArray();
});
