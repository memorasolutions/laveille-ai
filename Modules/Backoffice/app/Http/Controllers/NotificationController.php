<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Modules\Notifications\Notifications\SystemAlertNotification;

class NotificationController
{
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        return view('backoffice::notifications.index', compact('notifications'));
    }

    public function destroy(string $id): RedirectResponse
    {
        auth()->user()->notifications()->where('id', $id)->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    public function broadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|in:info,warning,critical',
            'message' => 'required|string|max:1000',
        ]);

        $users = User::all();

        Notification::send($users, new SystemAlertNotification($validated['level'], $validated['message']));

        return back()->with('success', count($users).' utilisateur(s) notifié(s).');
    }
}
