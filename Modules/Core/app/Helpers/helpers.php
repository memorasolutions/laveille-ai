<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
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

if (! function_exists('lv_social')) {
    /**
     * URL d'un réseau social configuré dans Settings, avec fallback centralisé.
     *
     * S90 #43 — Source unique de vérité pour les URLs sociales (DRY).
     * Élimine la duplication du pattern \Modules\Settings\Facades\Settings::get('social.X', 'hardcoded')
     * répartie dans 8+ fichiers (header, footer, master, leaderboard, emails newsletter, etc.).
     *
     * Plateformes supportées : facebook, messenger, twitter, linkedin, github, instagram, youtube.
     */
    function lv_social(string $platform): string
    {
        static $defaults = [
            'facebook' => 'https://www.facebook.com/LaVeilleAI',
            'messenger' => 'https://m.me/LaVeilleAI',
            'twitter' => 'https://x.com/laveille',
            'linkedin' => 'https://www.linkedin.com/in/lapointestephane/',
            'github' => 'https://github.com/memorasolutions',
            'instagram' => 'https://www.instagram.com/laveille.ai',
            'youtube' => 'https://www.youtube.com/@laveille-ai',
        ];

        static $keyMap = [
            'facebook' => 'social.facebook_page_url',
            'messenger' => 'social.messenger_url',
            'twitter' => 'social.twitter_url',
            'linkedin' => 'social.linkedin_url',
            'github' => 'social.github_url',
            'instagram' => 'social.instagram_url',
            'youtube' => 'social.youtube_url',
        ];

        $settingKey = $keyMap[$platform] ?? "social.{$platform}_url";
        $fallback = $defaults[$platform] ?? '';

        try {
            $value = \Modules\Settings\Facades\Settings::get($settingKey, $fallback);
            return is_string($value) && $value !== '' ? $value : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
