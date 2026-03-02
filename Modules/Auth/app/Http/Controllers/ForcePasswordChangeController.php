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
use Modules\Auth\Rules\PasswordHistoryRule;
use Modules\Auth\Rules\PasswordNotCompromisedRule;
use Modules\Auth\Rules\PasswordPolicyRule;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth::livewire.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', new PasswordPolicyRule, new PasswordNotCompromisedRule, new PasswordHistoryRule($request->user()->id)],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('user.dashboard')
            ->with('success', 'Mot de passe modifié avec succès.');
    }
}
