<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;

test('format_date formats correctly', function () {
    $date = Carbon::create(2026, 2, 15);
    expect(format_date($date))->toBe('15/02/2026');
    expect(format_date(null))->toBe('-');
});

test('format_datetime formats correctly', function () {
    $date = Carbon::create(2026, 2, 15, 14, 30);
    expect(format_datetime($date))->toBe('15/02/2026 14:30');
    expect(format_datetime(null))->toBe('-');
});
