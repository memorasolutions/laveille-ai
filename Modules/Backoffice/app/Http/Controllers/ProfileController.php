<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Auth\Rules\PasswordHistoryRule;
use Modules\Auth\Rules\PasswordNotCompromisedRule;
use Modules\Auth\Rules\PasswordPolicyRule;
use Modules\Auth\Services\TwoFactorService;

class ProfileController
{
    public function edit(): View
    {
        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $parsed = $this->parseUserAgent($session->user_agent);

                return (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'browser' => $parsed['browser'],
                    'os' => $parsed['os'],
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'is_current' => $session->id === session()->getId(),
                ];
            });

        return view('backoffice::profile.edit', [
            'user' => auth()->user(),
            'sessions' => $sessions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->input('_section') === 'bio') {
            $request->validate([
                'bio' => ['nullable', 'string', 'max:500'],
                'avatar' => ['nullable', 'image', 'max:2048'],
            ]);

            $data = ['bio' => $request->input('bio')];

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = 'storage/'.$path;
            }

            auth()->user()->update($data);

            return back()->with('success', __('Bio et photo mises à jour.'));
        }

        if ($request->input('_section') === 'social') {
            $validated = $request->validate([
                'social_links' => ['nullable', 'array'],
                'social_links.*' => ['nullable', 'url', 'max:255'],
            ]);

            auth()->user()->update([
                'social_links' => array_filter($validated['social_links'] ?? []),
            ]);

            return back()->with('success', __('Liens sociaux mis à jour.'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
        ]);

        auth()->user()->update($validated);

        return back()->with('success', __('Profil mis à jour.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', new PasswordPolicyRule, new PasswordNotCompromisedRule, new PasswordHistoryRule(auth()->id())],
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

    public function revokeSession(string $id): RedirectResponse
    {
        DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'Session révoquée.');
    }

    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        return back()->with('success', 'Toutes les autres sessions ont été révoquées.');
    }

    private function parseUserAgent(?string $ua): array
    {
        if (empty($ua)) {
            return ['browser' => 'Inconnu', 'os' => 'Inconnu'];
        }

        $browser = 'Inconnu';
        if (str_contains($ua, 'Edg') || str_contains($ua, 'Edge')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($ua, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Safari')) {
            $browser = 'Safari';
        }

        $os = 'Inconnu';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $os = 'iOS';
        } elseif (str_contains($ua, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($ua, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($ua, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($ua, 'Linux')) {
            $os = 'Linux';
        }

        return ['browser' => $browser, 'os' => $os];
    }
}
