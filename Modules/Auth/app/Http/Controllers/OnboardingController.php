<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Models\OnboardingStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnboardingController
{
    public function index(): View
    {
        $steps = OnboardingStep::active()->ordered()->get();

        return view('auth::onboarding.wizard', compact('steps'));
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($request->filled('name')) {
            $user->name = $request->input('name');
        }
        if ($request->filled('bio')) {
            $user->bio = $request->input('bio');
        }

        $user->onboarding_completed_at = now();
        $user->save();

        return redirect()->route('user.dashboard')->with('success', 'Bienvenue! Votre configuration est terminée.');
    }

    public function skip(): RedirectResponse
    {
        $user = Auth::user();
        $user->onboarding_completed_at = now();
        $user->save();

        return redirect()->route('user.dashboard');
    }
}
