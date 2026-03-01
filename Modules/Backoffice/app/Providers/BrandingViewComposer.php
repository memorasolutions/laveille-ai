<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
            'primary_color' => '#487FFF',
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
