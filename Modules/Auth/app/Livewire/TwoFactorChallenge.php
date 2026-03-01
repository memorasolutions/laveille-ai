<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Auth\Services\TwoFactorService;

#[Layout('auth::layouts.guest')]
class TwoFactorChallenge extends Component
{
    public string $code = '';

    public string $recoveryCode = '';

    public bool $usingRecoveryCode = false;

    public function authenticate(TwoFactorService $service): void
    {
        /** @var User|null $user */
        $user = User::find(session('auth.2fa_user_id'));

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => 'Session expirée. Veuillez vous reconnecter.',
            ]);
        }

        $valid = $this->usingRecoveryCode
            ? $service->verifyRecoveryCode($user, trim($this->recoveryCode))
            : $service->verify($user, trim($this->code));

        if (! $valid) {
            $field = $this->usingRecoveryCode ? 'recoveryCode' : 'code';

            throw ValidationException::withMessages([
                $field => 'Code invalide. Veuillez réessayer.',
            ]);
        }

        Auth::loginUsingId($user->id);
        session()->forget('auth.2fa_user_id');
        session(['auth.2fa_confirmed' => true]);
        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: false);
    }

    public function toggleRecoveryMode(): void
    {
        $this->usingRecoveryCode = ! $this->usingRecoveryCode;
        $this->code = '';
        $this->recoveryCode = '';
        $this->resetErrorBag();
    }

    public function render(): \Illuminate\View\View
    {
        return view('auth::livewire.two-factor-challenge');
    }
}
