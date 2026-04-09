<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Auth\Notifications\LockoutContactNotification;
use Modules\Auth\Services\AuthService;
use Modules\Settings\Facades\Settings;

#[Layout('auth::layouts.guest')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:6')]
    public string $password = '';

    public bool $remember = false;

    public bool $isLocked = false;

    public int $lockoutMinutes = 0;

    public string $contactMessage = '';

    public bool $contactSent = false;

    public function contactAdmin(): void
    {
        $this->validate(['contactMessage' => 'required|min:10|max:1000']);

        $admins = User::permission('manage_users')->get();

        Notification::send($admins, new LockoutContactNotification(
            $this->email,
            $this->contactMessage,
            request()->ip() ?? '0.0.0.0'
        ));

        $this->contactSent = true;
        $this->contactMessage = '';
    }

    public function authenticate(AuthService $authService): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        // Vérifier si le compte est verrouillé
        $user = User::where('email', $this->email)->first();
        if ($user && $user->isLocked()) {
            $this->isLocked = true;
            $this->lockoutMinutes = max(1, (int) ceil(now()->diffInMinutes($user->locked_until, false)));

            return;
        }

        // Vérifier si le rôle de l'utilisateur nécessite un mot de passe
        if ($user && ! $user->roleRequiresPassword()) {
            $this->redirect(route('magic-link.request', ['email' => $this->email]), navigate: false);

            return;
        }

        if (! $authService->authenticate($this->email, $this->password, $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            $freshUser = $user ? $user->fresh() : null;

            if ($freshUser && $freshUser->isLocked()) {
                $this->isLocked = true;
                $this->lockoutMinutes = (int) Settings::get('security.lockout_duration', 30);

                return;
            }

            $maxAttempts = (int) Settings::get('security.max_login_attempts', 5);
            $remainingAttempts = max(0, $maxAttempts - ($freshUser->failed_login_count ?? 0));
            throw ValidationException::withMessages([
                'email' => __('Ces identifiants ne correspondent pas. Il vous reste :attempts tentative(s) avant le verrouillage de votre compte.', ['attempts' => $remainingAttempts]),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::user();

        if ($user->two_factor_confirmed_at !== null) {
            Auth::logout();
            session(['auth.2fa_user_id' => $user->id]);
            $this->redirect(route('auth.two-factor-challenge'), navigate: false);

            return;
        }

        $oldSessionId = session()->getId();
        session()->regenerate();

        // Synchroniser le panier guest → user
        if (class_exists(\Modules\Shop\Services\CartService::class)) {
            app(\Modules\Shop\Services\CartService::class)->syncSessionCart($oldSessionId, Auth::id());
        }

        if ($user->must_change_password) {
            $this->redirect(route('password.force-change'), navigate: false);

            return;
        }

        $this->redirectIntended(default: route('user.dashboard'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('auth::livewire.login');
    }
}
