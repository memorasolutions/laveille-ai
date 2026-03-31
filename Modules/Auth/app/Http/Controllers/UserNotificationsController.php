<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Settings\Facades\Settings;

class UserNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->paginate((int) Settings::get('auth.user_notifications_per_page', 20));
        $unreadCount = $user->unreadNotifications()->count();

        return view('auth::notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function markRead(string $id): RedirectResponse
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        auth()->user()->notifications()->findOrFail($id)->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    public function destroyAll(): RedirectResponse
    {
        auth()->user()->notifications()->delete();

        return redirect()->route('user.notifications')->with('success', 'Toutes les notifications ont été supprimées.');
    }

    public function updateFrequency(Request $request): RedirectResponse
    {
        $request->validate([
            'notification_frequency' => 'required|in:immediate,daily,weekly',
        ]);

        $request->user()->update([
            'notification_frequency' => $request->input('notification_frequency'),
        ]);

        return back()->with('success', __('Préférence de notification mise à jour.'));
    }
}
