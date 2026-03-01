<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Auth\Rules\PasswordPolicyRule;
use Modules\Auth\Services\TwoFactorService;

class ProfileController
{
    public function edit(): View
    {
        return view('backoffice::profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
        ]);

        auth()->user()->update($validated);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', new PasswordPolicyRule],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe modifié.');
    }

    public function enableTwoFactor(TwoFactorService $service): RedirectResponse
    {
        $data = $service->enable(auth()->user());
        session(['2fa.setup' => $data]);

        return back()->with('status', '2fa-setup');
    }

    public function confirmTwoFactor(Request $request, TwoFactorService $service): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $service->confirm(auth()->user(), $request->code)) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }

        session()->forget('2fa.setup');

        return back()->with('success', 'Double authentification activée avec succès.');
    }

    public function disableTwoFactor(Request $request, TwoFactorService $service): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $service->disable(auth()->user());
        session()->forget('2fa.setup');
        session()->forget('auth.2fa_confirmed');

        return back()->with('success', 'Double authentification désactivée.');
    }
}
