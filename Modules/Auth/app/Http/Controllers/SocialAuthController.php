<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'github', 'microsoft', 'facebook', 'linkedin', 'x', 'apple'];

    public function redirect(string $provider): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404);
        }

        $scopes = $this->getScopes($provider);

        if (empty($scopes)) {
            return Socialite::driver($provider)->redirect();
        }

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->scopes($scopes)->redirect();
    }

    private function getScopes(string $provider): array
    {
        return match ($provider) {
            'google' => ['openid', 'profile', 'email'],
            'github' => ['user:email'],
            'microsoft' => ['openid', 'profile', 'email', 'User.Read'],
            'facebook' => ['email', 'public_profile'],
            'linkedin' => ['openid', 'profile', 'email'],
            'apple' => ['name', 'email'],
            default => [],
        };
    }

    public function callback(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Connexion sociale échouée. Veuillez réessayer.');
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update social info if not set
            if (! $user->social_provider) {
                $user->update([
                    'social_provider' => $provider,
                    'social_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'password' => bcrypt(Str::random(32)),
                'social_provider' => $provider,
                'social_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ]);

            $userRole = Role::firstOrCreate(['name' => 'user']);
            $user->assignRole($userRole);
        }

        Auth::login($user, true);

        return redirect()->intended(route('admin.dashboard'));
    }
}
