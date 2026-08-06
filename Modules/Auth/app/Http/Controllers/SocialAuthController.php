<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

            Auth::login($user, true);

            return redirect()->intended($user->homeRoute());
        }

        // Nouvel utilisateur : ne PAS créer le compte tout de suite. L'attestation d'âge
        // (config privacy.minors.eu_age, exigée depuis aujourd'hui à l'inscription classique -
        // Modules/Auth/app/Livewire/Register.php) doit aussi s'appliquer à l'inscription sociale,
        // sinon elle est contournée. On stocke les données Socialite en session le temps de faire
        // confirmer l'attestation sur un petit écran dédié, puis storeFinalize() crée le compte.
        // Le fournisseur social ne donne généralement qu'un seul champ "name" :
        // split naïf best-effort en first_name/last_name pour peupler les 2
        // colonnes sans changer le comportement existant de "name".
        $fullName = $socialUser->getName() ?? $socialUser->getNickname() ?? 'User';
        $parts = explode(' ', trim($fullName), 2);

        session(['social_registration' => [
            'provider' => $provider,
            'social_id' => $socialUser->getId(),
            'email' => $socialUser->getEmail(),
            'name' => $fullName,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'avatar' => $socialUser->getAvatar(),
        ]]);

        return redirect()->route('social.finalize');
    }

    /**
     * Écran « Finaliser l'inscription » : affiche l'attestation d'âge obligatoire avant de
     * créer le compte social. Aucune donnée de naissance n'est jamais demandée ni stockée.
     */
    public function showFinalize(): View|RedirectResponse
    {
        if (! session()->has('social_registration')) {
            return redirect()->route('register')->with('error', "Votre session d'inscription a expiré. Veuillez réessayer.");
        }

        return view('auth::social-finalize', [
            'data' => session('social_registration'),
        ]);
    }

    public function storeFinalize(Request $request): RedirectResponse
    {
        $data = session('social_registration');

        if (! $data) {
            return redirect()->route('register')->with('error', "Votre session d'inscription a expiré. Veuillez réessayer.");
        }

        $request->validate([
            'age_attested' => ['required', 'accepted'],
        ]);

        // Anti-course : l'utilisateur a pu se créer un compte par un autre canal pendant qu'il
        // était sur l'écran d'attestation - on se connecte plutôt que de tenter une création en
        // doublon (qui échouerait de toute façon sur la contrainte unique email).
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            $user = User::create([
                'name' => $data['name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => bcrypt(Str::random(32)),
                'social_provider' => $data['provider'],
                'social_id' => $data['social_id'],
                'avatar' => $data['avatar'],
                'email_verified_at' => now(),
            ]);

            $userRole = Role::firstOrCreate(['name' => 'user']);
            $user->assignRole($userRole);
        }

        session()->forget('social_registration');

        Auth::login($user, true);

        return redirect()->intended($user->homeRoute());
    }
}
