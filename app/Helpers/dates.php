<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Carbon\Carbon;

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
            'long' => 'D MMMM YYYY',
            'short' => 'D MMM YYYY',
            'datetime' => 'D MMM YYYY [à] HH:mm',
            'time' => 'HH:mm',
        ];

        if ($type === 'relative') {
            return $carbon->diffForHumans();
        }

        if ($type === 'iso') {
            return $carbon->toISOString();
        }

        // Bug 2026-07-05 : l'ancien fallback littéral ('d MMM YYYY', d minuscule = jour de la
        // SEMAINE en tokens Moment.js/isoFormat, pas jour du MOIS) avait été copié tel quel dans
        // la table settings (seed du 2026-03-20) au lieu du bon défaut 'D' majuscule ci-dessus.
        // Résultat : le badge de date des actualités affichait l'index du jour de semaine (0-6)
        // à la place du quantième, avec parfois un vrai changement de mois erroné. Le format
        // correct ('D' majuscule) est désormais la SEULE source de fallback (plus de littéral
        // dupliqué), et Setting::get() gère déjà son propre cache (clé "setting.{$key}",
        // invalidée par Setting::set()) : on ne rajoute plus de second niveau de cache ici, qui
        // pouvait servir une valeur corrigée en base mais encore périmée jusqu'à 1h.
        $settingKey = "date.format_{$type}";
        $fallback = $defaults[$type] ?? 'D MMM YYYY';

        $format = class_exists(\Modules\Settings\Models\Setting::class)
            ? \Modules\Settings\Models\Setting::get($settingKey, $fallback)
            : $fallback;

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
                    'D MMMM YYYY' => $now->isoFormat('D MMMM YYYY'),
                    'dddd D MMMM YYYY' => $now->isoFormat('dddd D MMMM YYYY'),
                ],
            ],
            'short' => [
                'label' => __('Date courte'),
                'formats' => [
                    'D MMM YYYY' => $now->isoFormat('D MMM YYYY'),
                    'DD/MM/YYYY' => $now->isoFormat('DD/MM/YYYY'),
                    'YYYY-MM-DD' => $now->isoFormat('YYYY-MM-DD'),
                    'DD-MM-YYYY' => $now->isoFormat('DD-MM-YYYY'),
                ],
            ],
            'datetime' => [
                'label' => __('Date et heure'),
                'formats' => [
                    'D MMM YYYY [à] HH:mm' => $now->isoFormat('D MMM YYYY [à] HH:mm'),
                    'DD/MM/YYYY HH:mm' => $now->isoFormat('DD/MM/YYYY HH:mm'),
                    'dddd D MMMM YYYY [à] HH:mm' => $now->isoFormat('dddd D MMMM YYYY [à] HH:mm'),
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
