<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Ecommerce\Services\Tax\CanadaTaxCalculator;
use Modules\Ecommerce\Services\Tax\TaxResult;
use Modules\Ecommerce\Services\TaxService;

uses(Tests\TestCase::class);

// --- TaxResult value object ---

test('tax result holds total and breakdown', function () {
    $result = new TaxResult(14.98, [
        ['name' => 'GST', 'rate' => 0.05, 'amount' => 5.0],
        ['name' => 'QST', 'rate' => 0.09975, 'amount' => 9.98],
    ]);

    expect($result->getTotal())->toBe(14.98)
        ->and($result->getBreakdown())->toHaveCount(2);
});

// --- CanadaTaxCalculator ---

test('canada tax QC calculates GST + QST', function () {
    $calc = new CanadaTaxCalculator;
    $result = $calc->calculateTax(100.0, 'QC');

    expect($result->getTotal())->toBe(14.98)
        ->and($result->getBreakdown())->toHaveCount(2)
        ->and($result->getBreakdown()[0]['name'])->toBe('GST')
        ->and($result->getBreakdown()[0]['amount'])->toBe(5.0)
        ->and($result->getBreakdown()[1]['name'])->toBe('QST')
        ->and($result->getBreakdown()[1]['amount'])->toBe(9.98);
});

test('canada tax ON calculates HST 13%', function () {
    $calc = new CanadaTaxCalculator;
    $result = $calc->calculateTax(100.0, 'ON');

    expect($result->getTotal())->toBe(13.0)
        ->and($result->getBreakdown())->toHaveCount(1)
        ->and($result->getBreakdown()[0]['name'])->toBe('HST');
});

test('canada tax AB calculates GST only 5%', function () {
    $calc = new CanadaTaxCalculator;
    $result = $calc->calculateTax(200.0, 'AB');

    expect($result->getTotal())->toBe(10.0)
        ->and($result->getBreakdown())->toHaveCount(1)
        ->and($result->getBreakdown()[0]['name'])->toBe('GST');
});

test('canada tax NS calculates HST 15%', function () {
    $calc = new CanadaTaxCalculator;
    $result = $calc->calculateTax(100.0, 'NS');

    expect($result->getTotal())->toBe(15.0);
});

test('canada tax BC calculates GST + PST', function () {
    $calc = new CanadaTaxCalculator;
    $result = $calc->calculateTax(100.0, 'BC');

    expect($result->getTotal())->toBe(12.0)
        ->and($result->getBreakdown())->toHaveCount(2)
        ->and($result->getBreakdown()[1]['name'])->toBe('PST');
});

test('canada tax unknown province defaults to QC', function () {
    $calc = new CanadaTaxCalculator;
    $result = $calc->calculateTax(100.0, 'XX');

    expect($result->getTotal())->toBe(14.98);
});

test('canada tax is case insensitive', function () {
    $calc = new CanadaTaxCalculator;
    expect($calc->calculateTax(100.0, 'qc')->getTotal())->toBe(14.98)
        ->and($calc->calculateTax(100.0, 'on')->getTotal())->toBe(13.0);
});

// --- TaxService backward compatibility ---

test('tax service without province uses flat rate', function () {
    $service = app(TaxService::class);
    expect($service->calculateTax(100.0))->toBe(14.98);
});

test('tax service with province uses calculator', function () {
    $service = app(TaxService::class);
    expect($service->calculateTax(100.0, 'ON'))->toBe(13.0)
        ->and($service->calculateTax(100.0, 'AB'))->toBe(5.0);
});

test('tax service getTaxBreakdown returns detailed breakdown', function () {
    $service = app(TaxService::class);
    $breakdown = $service->getTaxBreakdown(100.0, 'QC');

    expect($breakdown)->toHaveCount(2)
        ->and($breakdown[0]['name'])->toBe('GST')
        ->and($breakdown[1]['name'])->toBe('QST');
});

test('tax service calculateTaxResult returns TaxResult object', function () {
    $service = app(TaxService::class);
    $result = $service->calculateTaxResult(100.0, 'ON');

    expect($result)->toBeInstanceOf(TaxResult::class)
        ->and($result->getTotal())->toBe(13.0);
});
