<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public int $step = 0;

    public string $bio = '';

    public bool $dismissed = false;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->step = $user->onboarding_step ?? 0;
        $this->bio = $user->bio ?? '';
        $this->dismissed = $user->onboarding_completed_at !== null;
    }

    public function completeStep(int $newStep): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($newStep > $user->onboarding_step) {
            $user->update(['onboarding_step' => $newStep]);
        }

        $this->step = $newStep;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'bio' => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['bio' => $this->bio]);

        $this->completeStep(2);
    }

    public function skipToStep(int $newStep): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($newStep > $user->onboarding_step) {
            $user->update(['onboarding_step' => $newStep]);
        }

        $this->step = $newStep;
    }

    public function complete(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'onboarding_step' => 5,
            'onboarding_completed_at' => now(),
        ]);

        $this->dismissed = true;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('auth::livewire.onboarding-wizard');
    }
}
