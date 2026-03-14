<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Settings\Models\Setting;

class BrandingViewComposer
{
    public function compose(View $view): void
    {
        $branding = Cache::remember('branding_settings', 3600, function () {
            $settings = Setting::where('group', 'branding')
                ->pluck('value', 'key')
                ->toArray();

            // Retirer le préfixe "branding." des clés pour simplifier l'usage dans les vues
            $cleaned = [];
            foreach ($settings as $key => $value) {
                $cleaned[str_replace('branding.', '', $key)] = $value;
            }

            return $cleaned;
        });

        // Valeurs par défaut si les settings n'existent pas encore
        $defaults = [
            'site_name' => Setting::get('site_name') ?: config('app.name', 'Laravel'),
            'site_description' => Setting::get('site_description') ?: '',
            'logo_light' => '',
            'logo_dark' => '',
            'logo_icon' => '',
            'favicon' => '',
            'primary_color' => '#6571ff',
            'secondary_color' => '#7987a1',
            'success_color' => '#05a34a',
            'warning_color' => '#fbbc06',
            'danger_color' => '#ff3366',
            'info_color' => '#66d1d1',
            'sidebar_bg' => '#0c1427',
            'header_bg' => '#ffffff',
            'body_bg' => '#ffffff',
            'topbar_font_family' => 'Roboto',
            'topbar_font_size' => '1.25rem',
            'topbar_font_weight' => '700',
            'topbar_letter_spacing' => '0px',
            'topbar_word_spacing' => '0px',
            'topbar_text_transform' => 'none',
            'font_family' => 'Inter',
            'font_url' => '',
            'footer_text' => '',
            'footer_right' => '',
            'login_title' => 'Connexion',
            'login_subtitle' => '',
        ];

        $branding = array_merge($defaults, array_filter($branding, fn ($v) => $v !== '' && $v !== null));

        $view->with('branding', $branding);
    }
}
