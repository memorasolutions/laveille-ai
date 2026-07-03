<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// format_date() N'EST PAS DÉFINIE ICI : app/Helpers/dates.php (chargé en amont via
// composer.json autoload.files) fournit LA version canonique, configurable via Settings.
// Voir Modules/Core/tests/Unit/HelpersTest.php pour le contrat attendu.

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

if (! function_exists('safe_excerpt')) {
    /**
     * Extrait sécurisé (meta description, résumés) qui ne coupe JAMAIS en plein milieu d'un mot.
     *
     * Bug SEO confirmé (audit 2026-07-03, /glossaire/modele-frontiere) : Str::limit() coupe
     * par nombre de caractères pur, sans respecter les limites de mots ("...comporte d...").
     * Une meta description tronquée en plein mot dans les SERP Google décourage le clic.
     *
     * @param  string|null  $text
     * @param  int  $limit
     * @param  string  $end
     * @return string
     */
    function safe_excerpt(?string $text, int $limit = 160, string $end = '...'): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $clean = trim(strip_tags($text));

        if ($clean === '') {
            return '';
        }

        if (mb_strlen($clean) <= $limit) {
            return $clean;
        }

        $truncated = mb_substr($clean, 0, $limit);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        $truncated = rtrim($truncated);

        return Str::of($truncated)->append($end)->toString();
    }
}
