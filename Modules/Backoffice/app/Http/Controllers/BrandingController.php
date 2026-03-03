<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Core\Services\GoogleFontService;
use Modules\Settings\Models\Setting;

class BrandingController
{
    public function edit(): View
    {
        $branding = Setting::where('group', 'branding')
            ->pluck('value', 'key')
            ->toArray();

        // Retirer le préfixe "branding." des clés
        $settings = [];
        foreach ($branding as $key => $value) {
            $settings[str_replace('branding.', '', $key)] = $value;
        }

        // Injecter site_name et site_description depuis les settings "general"
        $settings['site_name'] = Setting::get('site_name') ?: config('app.name', 'Laravel');
        $settings['site_description'] = Setting::get('site_description') ?: '';

        return view('backoffice::branding.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'success_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'warning_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'danger_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'info_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_bg' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_bg' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'body_bg' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'topbar_font_family' => ['nullable', 'string', 'max:100'],
            'topbar_font_size' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(rem|px|em)$/'],
            'topbar_font_weight' => ['nullable', 'string', 'in:300,400,500,700,900'],
            'topbar_letter_spacing' => ['nullable', 'string', 'regex:/^-?\d+(\.\d+)?px$/'],
            'topbar_word_spacing' => ['nullable', 'string', 'regex:/^-?\d+(\.\d+)?px$/'],
            'topbar_text_transform' => ['nullable', 'string', 'in:none,uppercase,capitalize'],
            'font_family' => ['required', 'string', 'max:100'],
            'font_url' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'footer_right' => ['nullable', 'string', 'max:500'],
            'login_title' => ['nullable', 'string', 'max:255'],
            'login_subtitle' => ['nullable', 'string', 'max:500'],
            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'logo_icon' => ['nullable', 'image', 'max:1024'],
            'favicon' => ['nullable', 'image', 'max:512'],
        ]);

        // Upload des fichiers
        $fileFields = ['logo_light', 'logo_dark', 'logo_icon', 'favicon'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('branding', 'public');
                Setting::set("branding.{$field}", $path, 'string', 'branding');
            }
        }

        // Sauvegarder site_name et site_description dans le groupe "general" (source unique)
        Setting::set('site_name', $validated['site_name'] ?? '', 'string', 'general');
        Setting::set('site_description', $validated['site_description'] ?? '', 'string', 'general');

        // Télécharger la police Google Fonts localement si elle change
        $fontFamily = $validated['font_family'] ?? 'Inter';
        $fontService = app(GoogleFontService::class);

        if ($fontFamily !== 'Inter' && ! $fontService->isDownloaded($fontFamily)) {
            $localCssPath = $fontService->download($fontFamily);
            if ($localCssPath !== '') {
                $validated['font_url'] = $localCssPath;
            }
        } elseif ($fontFamily !== 'Inter' && $fontService->isDownloaded($fontFamily)) {
            $validated['font_url'] = $fontService->getLocalCssPath($fontFamily);
        } else {
            $validated['font_url'] = '';
        }

        // Sauvegarder les champs texte branding
        $textFields = [
            'primary_color', 'secondary_color', 'success_color', 'warning_color',
            'danger_color', 'info_color', 'sidebar_bg', 'header_bg', 'body_bg',
            'topbar_font_family', 'topbar_font_size', 'topbar_font_weight',
            'topbar_letter_spacing', 'topbar_word_spacing', 'topbar_text_transform',
            'font_family', 'font_url',
            'footer_text', 'footer_right', 'login_title', 'login_subtitle',
        ];
        foreach ($textFields as $field) {
            Setting::set("branding.{$field}", $validated[$field] ?? '', 'string', 'branding');
        }

        // Vider le cache branding
        Cache::forget('branding_settings');

        return redirect()->route('admin.branding.edit')->with('success', 'Personnalisation enregistrée.');
    }
}
