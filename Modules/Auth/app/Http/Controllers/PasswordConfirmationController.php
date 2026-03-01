<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordConfirmationController extends Controller
{
    /**
     * Display the password confirmation view.
     */
    public function show(): View
    {
        return view('auth::livewire.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('user.dashboard'));
    }
}
