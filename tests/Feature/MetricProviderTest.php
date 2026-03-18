<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;
use Modules\Core\Services\MetricAggregatorService;
use Modules\Ecommerce\Providers\EcommerceMetricProvider;

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

test('EcommerceMetricProvider implements interface', function () {
    $provider = app(EcommerceMetricProvider::class);

    expect($provider)->toBeInstanceOf(MetricProviderInterface::class)
        ->and($provider->getMetricName())->toBe('ecommerce');
});

test('EcommerceMetricProvider returns 5 widgets', function () {
    $provider = app(EcommerceMetricProvider::class);
    $widgets = $provider->getWidgets();

    expect($widgets)->toHaveCount(5);
    expect($widgets[0])->toBeInstanceOf(MetricWidget::class);
});

test('EcommerceMetricProvider returns metrics array', function () {
    $provider = app(EcommerceMetricProvider::class);
    $metrics = $provider->getMetrics(now()->startOfMonth(), now()->endOfMonth());

    expect($metrics)->toHaveKeys(['revenue', 'orders_count', 'avg_order', 'products_active', 'refund_rate']);
});

test('MetricAggregatorService discovers tagged providers', function () {
    $aggregator = app(MetricAggregatorService::class);
    $providers = $aggregator->getRegisteredProviders();

    expect($providers)->toContain('ecommerce');
});

test('MetricAggregatorService getAllWidgets collects from all providers', function () {
    $aggregator = app(MetricAggregatorService::class);
    $widgets = $aggregator->getAllWidgets();

    expect($widgets)->not->toBeEmpty();
    expect($widgets[0])->toBeInstanceOf(MetricWidget::class);
});
