<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
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
            'font_family' => ['required', 'string', 'max:100'],
            'font_url' => ['nullable', 'url', 'max:500'],
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

        // Sauvegarder les champs texte branding
        $textFields = [
            'primary_color', 'font_family', 'font_url',
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
