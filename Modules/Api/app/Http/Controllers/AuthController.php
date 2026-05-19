<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Api\Http\Requests\LoginRequest;
use Modules\Api\Http\Requests\RegisterRequest;
use Modules\Api\Mail\RegistrationAttemptMail;
use Modules\Auth\Http\Resources\UserResource;

/**
 * @group Authentication
 *
 * Endpoints for registering, logging in and managing the current session.
 */
class AuthController extends BaseApiController
{
    /**
     * Log in with email and password and receive a Sanctum token.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->respondUnauthorized('Identifiants invalides.');
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->respondSuccess([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ]);
    }

    /**
     * Register a new user account.
     *
     * SÉCURITÉ #254 (v1.19.15) — Anti user enumeration.
     *
     * Pour ne pas révéler si une adresse courriel est déjà inscrite (Auth0/GitHub/Stripe 2026),
     * la réponse est STRICTEMENT IDENTIQUE que l'email existe ou non :
     *   HTTP 201 + { success: true, message: "Si l'adresse est valide, vous recevrez..." }
     *
     * Comportement :
     *  - Email INEXISTANT : crée le User normalement (sans renvoyer de token Sanctum) ;
     *  - Email EXISTANT   : NE crée PAS de doublon + envoie RegistrationAttemptMail
     *                       au propriétaire du compte pour l'avertir de la tentative.
     *
     * Breaking change UX (versus pré-v1.19.15) :
     *   L'ancienne réponse contenait `user` + `token` Sanctum permettant un auto-login
     *   immédiat après inscription. Cette fuite par structure/latence est désormais
     *   bloquée : le client doit appeler /login séparément (standard sécurité 2026).
     *
     * Loi 25 (QC) : le courriel RegistrationAttemptMail est transactionnel légitime
     * (sécurité du compte existant), pas de consentement marketing requis.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $email = (string) $request->validated('email');
        $existingUser = User::where('email', $email)->first();

        if ($existingUser !== null) {
            // Compte déjà inscrit : NE PAS créer de doublon. Avertir le propriétaire.
            try {
                Mail::to($existingUser->email)->send(new RegistrationAttemptMail($existingUser));
            } catch (\Throwable $e) {
                // Le mail ne doit JAMAIS faire échouer la réponse (sinon timing-side-channel
                // qui révélerait l'existence du compte). On log silencieusement.
                Log::warning('RegistrationAttemptMail send failed', [
                    'user_id' => $existingUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Email inconnu : création réelle du compte.
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $email,
                'password' => Hash::make($request->validated('password')),
            ]);
            $user->assignRole('user');
        }

        // Réponse 201 STRICTEMENT IDENTIQUE dans les deux cas — aucun token, aucun
        // user dans le payload, aucune différence de structure ou de timing exploitable.
        return $this->respondCreated(
            data: null,
            message: "Si l'adresse est valide, vous recevrez un courriel pour activer votre compte."
        );
    }

    /**
     * Revoke the current Sanctum token and log out.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respondSuccess(message: 'Déconnecté avec succès.');
    }

    /**
     * Return the authenticated user's profile with roles.
     */
    public function user(Request $request): JsonResponse
    {
        return $this->respondSuccess(new UserResource($request->user()->load('roles')));
    }
}
