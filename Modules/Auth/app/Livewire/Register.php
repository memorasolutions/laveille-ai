<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(AuthService $authService): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', new PasswordPolicyRule, new PasswordNotCompromisedRule],
        ]);

        $user = $authService->register([
            'name' => $this->name,
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
