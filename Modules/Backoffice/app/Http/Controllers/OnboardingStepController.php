<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\OnboardingStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingStepController
{
    public function index(): View
    {
        $steps = OnboardingStep::orderBy('order')->get();

        return view('backoffice::onboarding-steps.index', compact('steps'));
    }

    public function edit(OnboardingStep $onboardingStep): View
    {
        return view('backoffice::onboarding-steps.edit', ['step' => $onboardingStep]);
    }

    public function update(Request $request, OnboardingStep $onboardingStep): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $onboardingStep->update($validated);

        return redirect()->route('admin.onboarding-steps.index')->with('success', 'Étape mise à jour avec succès.');
    }
}
