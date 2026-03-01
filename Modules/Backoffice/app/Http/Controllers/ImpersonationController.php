<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function impersonate(User $user): RedirectResponse
    {
        if (! auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        if ($user->hasRole('super_admin')) {
            return back()->withErrors(['error' => "Impossible d'impersoner un super administrateur."]);
        }

        session(['impersonating_original_id' => auth()->id()]);

        Auth::login($user);

        return redirect()->route('user.dashboard')
            ->with('success', "Vous impersonez maintenant {$user->name}.");
    }

    public function stopImpersonating(): RedirectResponse
    {
        $originalId = session('impersonating_original_id');

        if (! $originalId) {
            abort(403);
        }

        session()->forget('impersonating_original_id');

        Auth::login(User::findOrFail($originalId));

        return redirect()->route('admin.dashboard')
            ->with('success', 'Impersonnification terminée.');
    }
}
