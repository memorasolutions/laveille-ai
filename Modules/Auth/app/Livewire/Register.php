<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Auth\Rules\PasswordNotCompromisedRule;
use Modules\Auth\Rules\PasswordPolicyRule;
use Modules\Auth\Services\AuthService;

#[Layout('auth::layouts.guest')]
class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Attestation d'age (CGU : compte reserve aux 16 ans et plus, config privacy.minors.eu_age).
    // Booleen uniquement, jamais de date de naissance stockee (minimisation des donnees).
    public bool $age_attested = false;

    public function register(AuthService $authService): void
    {
        $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', new PasswordPolicyRule, new PasswordNotCompromisedRule],
            'age_attested' => ['required', 'accepted'],
        ]);

        $name = trim($this->first_name.' '.$this->last_name);

        $user = $authService->register([
            'name' => $name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => $this->password,
        ]);

        event(new Registered($user));
        Auth::login($user);

        $this->redirect(route('user.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('auth::livewire.register');
    }
}
