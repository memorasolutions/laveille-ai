<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;

if (! function_exists('format_date')) {
    function format_date(?Carbon $date, string $format = 'd/m/Y'): string
    {
        return $date ? $date->format($format) : '-';
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime(?Carbon $date, string $format = 'd/m/Y H:i'): string
    {
        return $date ? $date->format($format) : '-';
    }
}

if (! function_exists('format_money')) {
    function format_money(float $amount, string $currency = 'CAD', string $locale = 'fr_CA'): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }
}

if (! function_exists('is_active_route')) {
    function is_active_route(string $route, string $class = 'active'): string
    {
        return request()->routeIs($route) ? $class : '';
    }
}
