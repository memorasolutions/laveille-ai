<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notifications\Jobs\SendWebPushNotification;
use NotificationChannels\WebPush\PushSubscription;

class PushNotificationController extends Controller
{
    public function index()
    {
        $count = PushSubscription::count();

        return view('backoffice::push-notifications.index', compact('count'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:500',
            'url' => 'nullable|url',
            'role' => 'nullable|string',
        ]);

        SendWebPushNotification::dispatch(
            $validated['title'],
            $validated['body'],
            $validated['url'] ?? '/',
            $validated['role'] ?? null
        );

        return redirect()->back()->with('success', 'Notification push envoyée avec succès');
    }
}
