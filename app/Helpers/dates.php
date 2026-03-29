<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

if (! function_exists('format_date')) {
    /**
     * Format a date using site-wide configurable settings.
     *
     * @param  mixed  $date  Carbon instance, string, or null
     * @param  string  $type  long|short|relative|datetime|time|iso|custom
     */
    function format_date(mixed $date, string $type = 'short'): string
    {
        if (empty($date)) {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        $defaults = [
            'long' => 'd MMMM YYYY',
            'short' => 'd MMM YYYY',
            'datetime' => 'd MMM YYYY [à] HH:mm',
            'time' => 'HH:mm',
        ];

        if ($type === 'relative') {
            return $carbon->diffForHumans();
        }

        if ($type === 'iso') {
            return $carbon->toISOString();
        }

        $settingKey = "date.format_{$type}";
        $format = Cache::remember("setting.date.{$type}", 3600, function () use ($settingKey, $type, $defaults) {
            if (class_exists(\Modules\Settings\Models\Setting::class)) {
                return \Modules\Settings\Models\Setting::get($settingKey, $defaults[$type] ?? 'd MMM YYYY');
            }

            return $defaults[$type] ?? 'd MMM YYYY';
        });

        return $carbon->isoFormat($format);
    }
}

if (! function_exists('format_date_options')) {
    /**
     * Return available date format presets for admin UI.
     */
    function format_date_options(): array
    {
        $now = Carbon::now();

        return [
            'long' => [
                'label' => __('Date longue'),
                'formats' => [
                    'd MMMM YYYY' => $now->isoFormat('d MMMM YYYY'),
                    'dddd d MMMM YYYY' => $now->isoFormat('dddd d MMMM YYYY'),
                    'D MMMM YYYY' => $now->isoFormat('D MMMM YYYY'),
                ],
            ],
            'short' => [
                'label' => __('Date courte'),
                'formats' => [
                    'd MMM YYYY' => $now->isoFormat('d MMM YYYY'),
                    'DD/MM/YYYY' => $now->isoFormat('DD/MM/YYYY'),
                    'YYYY-MM-DD' => $now->isoFormat('YYYY-MM-DD'),
                    'DD-MM-YYYY' => $now->isoFormat('DD-MM-YYYY'),
                ],
            ],
            'datetime' => [
                'label' => __('Date et heure'),
                'formats' => [
                    'd MMM YYYY [à] HH:mm' => $now->isoFormat('d MMM YYYY [à] HH:mm'),
                    'DD/MM/YYYY HH:mm' => $now->isoFormat('DD/MM/YYYY HH:mm'),
                    'dddd d MMMM YYYY [à] HH:mm' => $now->isoFormat('dddd d MMMM YYYY [à] HH:mm'),
                ],
            ],
            'time' => [
                'label' => __('Heure'),
                'formats' => [
                    'HH:mm' => $now->isoFormat('HH:mm'),
                    'HH:mm:ss' => $now->isoFormat('HH:mm:ss'),
                    'h:mm A' => $now->isoFormat('h:mm A'),
                ],
            ],
        ];
    }
}
