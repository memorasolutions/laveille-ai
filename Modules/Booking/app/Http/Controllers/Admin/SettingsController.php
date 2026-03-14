<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\BookingSetting;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = BookingSetting::all()->pluck('value', 'key');

        return view('booking::admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate(['settings' => 'required|array']);

        foreach ($request->input('settings') as $key => $value) {
            BookingSetting::set($key, $value);
        }

        return redirect()->route('admin.booking.settings.edit')
            ->with('success', __('Paramètres enregistrés.'));
    }
}
