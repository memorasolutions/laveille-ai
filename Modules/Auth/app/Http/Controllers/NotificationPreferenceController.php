<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Notifications\Services\NotificationPreferenceService;

class NotificationPreferenceController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $types = NotificationPreferenceService::configurableTypes();
        $preferences = NotificationPreferenceService::getPreferences($user);

        return view('auth::themes.backend.notifications.preferences', [
            'user' => $user,
            'types' => $types,
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $enabled = $request->input('preferences', []);

        $types = NotificationPreferenceService::configurableTypes();
        $prefs = [];

        foreach ($types as $type => $config) {
            foreach ($config['channels'] as $channel) {
                $key = $type.'.'.$channel;
                $prefs[$key] = isset($enabled[$key]);
            }
        }

        NotificationPreferenceService::updatePreferences($user, $prefs);

        return back()->with('success', __('Préférences de notification mises à jour.'));
    }
}
