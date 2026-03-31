<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Backoffice\Http\Requests\StoreSettingRequest;
use Modules\Backoffice\Http\Requests\UpdateSettingRequest;
use Modules\Settings\Facades\Settings;
use Modules\Settings\Models\Setting;

class SettingController
{
    public function index(): View
    {
        $settings = Setting::orderBy('group')->orderBy('key')->paginate((int) Settings::get('backoffice.settings_per_page', 25));

        return view('backoffice::settings.index', compact('settings'));
    }

    public function create(): View
    {
        return view('backoffice::settings.create');
    }

    public function store(StoreSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['type'] = $validated['type'] ?? 'string';
        $validated['is_public'] = $request->boolean('is_public');

        if ($validated['group'] === '__custom') {
            $validated['group'] = $request->input('custom_group', 'general');
        }

        Setting::create($validated);

        return redirect()->route('admin.settings.index')->with('success', 'Paramètre créé.');
    }

    public function edit(Setting $setting): View
    {
        return view('backoffice::settings.edit', compact('setting'));
    }

    public function update(UpdateSettingRequest $request, Setting $setting): RedirectResponse
    {
        $validated = $request->validated();

        $validated['type'] = $validated['type'] ?? 'string';
        $validated['is_public'] = $request->boolean('is_public');

        $setting->update($validated);

        return redirect()->route('admin.settings.index')->with('success', 'Paramètre modifié.');
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        $setting->delete();

        return redirect()->route('admin.settings.index')->with('success', 'Paramètre supprimé.');
    }
}
