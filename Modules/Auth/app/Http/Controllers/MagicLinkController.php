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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Auth\Notifications\MagicLinkNotification;
use Modules\Auth\Services\MagicLinkService;
use Modules\Notifications\Contracts\SmsDriverInterface;
use Modules\Settings\Facades\Settings;

class MagicLinkController extends Controller
{
    public function __construct(private readonly MagicLinkService $magicLink) {}

    /**
     * Alerter le fondateur quand l'OTP a dû basculer de Postmark vers Workspace.
     * La connexion est sauvée par le repli, mais la panne Postmark doit rester VISIBLE :
     * sans cette alerte, le repli masquerait silencieusement la dégradation. Passe par le
     * canal d'alerte existant (Mail::raw → mailer par défaut Workspace), jamais par Postmark.
     */
    private function alertPostmarkFallback(string $email, string $error): void
    {
        try {
            if (class_exists(\Modules\Notifications\Services\AutomationAlertService::class)) {
                \Modules\Notifications\Services\AutomationAlertService::fire(
                    'otp-mailer',
                    'OTP : repli Postmark vers Workspace',
                    'Postmark a échoué pour l\'envoi du code de connexion; repli automatique vers le SMTP Workspace (la connexion fonctionne toujours). Erreur Postmark : '.$error,
                    ['email' => $email]
                );
            }
        } catch (\Throwable $alertErr) {
            \Illuminate\Support\Facades\Log::error('Alerte repli OTP non envoyée', ['error' => $alertErr->getMessage()]);
        }
    }

    public function showRequestForm(): View
    {
        $frontView = 'fronttheme::auth.magic-link-request';
        if (class_exists(\Modules\FrontTheme\Providers\FrontThemeServiceProvider::class)) {
            return view($frontView);
        }

        return view('auth::livewire.magic-link-request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        $rateLimitKey = 'magic-link-email:'.sha1($request->email);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors(['email' => "Trop de tentatives. Réessayez dans {$seconds} secondes."]);
        }

        // Auto-créer le compte + assigner le rôle par défaut dans une transaction : une création
        // partielle (compte créé mais assignRole() en échec) laisserait sinon un compte orphelin
        // sans rôle de façon PERMANENTE (wasRecentlyCreated devient false dès le 2e essai, donc
        // assignRole() ne serait plus jamais rejoué) — trouvé par la simulation E2E du 2026-07-11.
        $user = DB::transaction(function () use ($request) {
            $user = User::firstOrCreate(
                ['email' => $request->email],
                ['name' => explode('@', $request->email)[0], 'password' => bcrypt(\Str::random(32))]
            );

            if ($user->wasRecentlyCreated) {
                $user->assignRole('user');
            }

            return $user;
        });

        $result = $this->magicLink->generate($request->email);

        // Ne consommer une tentative qu'APRÈS un envoi réussi : compter avant l'envoi (ancien
        // comportement) bloquait l'utilisateur une heure même quand le courriel ne partait pas
        // - incident Marc, 2026-10-02. Postmark d'abord (transactionnel dédié), repli automatique
        // vers Workspace si Postmark est indisponible : la connexion ne doit jamais tomber en panne.
        try {
            $user->notify(new MagicLinkNotification($result['token']));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Magic link Postmark indisponible, repli Workspace', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            $this->alertPostmarkFallback($request->email, $e->getMessage());
            try {
                $user->notify(new MagicLinkNotification($result['token'], 'workspace'));
            } catch (\Throwable $e2) {
                \Illuminate\Support\Facades\Log::error('Magic link email failed (postmark + workspace)', [
                    'email' => $request->email,
                    'error' => $e2->getMessage(),
                ]);

                return back()->withErrors(['email' => "L'envoi du code a échoué. Veuillez réessayer dans un instant."]);
            }
        }

        RateLimiter::hit($rateLimitKey, 3600);

        if (app()->environment('local')) {
            session(['dev_magic_code' => $result['token']]);
        }

        $expiryMinutes = (int) Settings::get('magic_link_expiry_minutes', 15);

        return redirect()->route('magic-link.verify', ['email' => $request->email])
            ->with('status', "Code de connexion envoyé par courriel. Valide {$expiryMinutes} minutes.");
    }

    public function sendSms(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        $smsRateLimitKey = 'magic-link-sms:'.sha1($request->email);
        if (RateLimiter::tooManyAttempts($smsRateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($smsRateLimitKey);

            return back()->withErrors(['sms' => "SMS déjà envoyé. Réessayez dans {$seconds} secondes."]);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user || ! $user->phone) {
            return back()->withErrors(['sms' => 'Aucun numéro de téléphone associé à ce compte.']);
        }

        if (! $this->magicLink->hasValidToken($request->email)) {
            $result = $this->magicLink->generate($request->email);
            $token = $result['token'];
        } else {
            $record = \Illuminate\Support\Facades\DB::table('magic_login_tokens')
                ->where('email', $request->email)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->first();
            $token = $record->token;
        }

        $smsDriver = app(SmsDriverInterface::class);
        $expiryMinutes = (int) Settings::get('magic_link_expiry_minutes', 15);
        $message = "Votre code de connexion : {$token}. Valide {$expiryMinutes} minutes.";
        $sent = $smsDriver->send($user->phone, $message);

        if (! $sent) {
            return back()->withErrors(['sms' => "Échec de l'envoi du SMS. Veuillez réessayer."]);
        }

        RateLimiter::hit($smsRateLimitKey, 600);

        return back()->with('sms_sent', 'Code envoyé par SMS.');
    }

    public function showVerifyForm(Request $request): View
    {
        $email = $request->get('email', '');
        $user = User::where('email', $email)->first();
        $hasPhone = $user && $user->phone && Settings::get('sms_enabled', false);
        $smsButtonDelay = (int) Settings::get('sms_button_delay_seconds', 10);
        $expiryMinutes = (int) Settings::get('magic_link_expiry_minutes', 15);

        if (class_exists(\Modules\FrontTheme\Providers\FrontThemeServiceProvider::class)) {
            return view('fronttheme::auth.magic-link-verify', compact('email', 'hasPhone', 'smsButtonDelay', 'expiryMinutes'));
        }

        return view('auth::livewire.magic-link-verify', compact('email', 'hasPhone', 'smsButtonDelay', 'expiryMinutes'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string|size:6',
        ]);

        $user = $this->magicLink->verify($request->email, $request->token);
        if (! $user) {
            return back()->withErrors(['token' => 'Code invalide ou expiré.'])->withInput();
        }

        if ($user->two_factor_confirmed_at !== null) {
            session(['auth.2fa_user_id' => $user->id]);

            return redirect()->route('auth.two-factor-challenge');
        }

        // Saisir correctement le code à 6 chiffres reçu par courriel EST la preuve de possession
        // de l'adresse — exiger en plus un clic sur un lien de vérification signé est redondant
        // et bloque à tort les fonctionnalités gatées par le middleware `verified` (ex. création
        // de journal) pour tout utilisateur connecté uniquement via OTP — trouvé par la
        // simulation E2E du 2026-07-11.
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $oldSessionId = session()->getId();
        auth()->login($user, true);

        // Synchroniser le panier guest → user (le login régénère le session_id)
        if (class_exists(\Modules\Shop\Services\CartService::class)) {
            app(\Modules\Shop\Services\CartService::class)->syncSessionCart($oldSessionId, $user->id);
        }

        if ($user->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended($user->homeRoute());
    }

    /**
     * API : envoyer un magic link (JSON, pour usage inline sans redirection).
     */
    public function sendLinkApi(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        // Auto-créer le compte + assigner le rôle par défaut dans une transaction (voir sendLink()
        // ci-dessus pour la raison : évite un compte créé sans rôle de façon permanente).
        $user = DB::transaction(function () use ($request) {
            $user = User::firstOrCreate(
                ['email' => $request->email],
                ['name' => explode('@', $request->email)[0], 'password' => bcrypt(\Str::random(32))]
            );

            if ($user->wasRecentlyCreated) {
                $user->assignRole('user');
            }

            return $user;
        });

        $rateLimitKey = 'magic-link-email:'.sha1($request->email);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json(['success' => false, 'message' => __('Trop de tentatives. Reessayez dans :seconds secondes.', ['seconds' => $seconds])], 429);
        }

        $result = $this->magicLink->generate($request->email);

        // Ne consommer une tentative qu'APRÈS un envoi réussi (voir sendLink()). Postmark d'abord,
        // repli automatique vers Workspace si Postmark est indisponible.
        try {
            $user->notify(new MagicLinkNotification($result['token']));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Magic link Postmark indisponible, repli Workspace (api)', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            $this->alertPostmarkFallback($request->email, $e->getMessage());
            try {
                $user->notify(new MagicLinkNotification($result['token'], 'workspace'));
            } catch (\Throwable $e2) {
                \Illuminate\Support\Facades\Log::error('Magic link email failed (api, postmark + workspace)', [
                    'email' => $request->email,
                    'error' => $e2->getMessage(),
                ]);

                return response()->json(['success' => false, 'message' => __("L'envoi du code a échoué. Veuillez réessayer.")], 500);
            }
        }

        RateLimiter::hit($rateLimitKey, 3600);

        return response()->json(['success' => true, 'message' => __('Code de connexion envoyé par courriel.')]);
    }

    /**
     * API : verifier le code OTP (JSON, pour usage inline sans redirection).
     */
    public function verifyApi(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string|size:6',
        ]);

        $user = $this->magicLink->verify($request->email, $request->token);
        if (! $user) {
            return response()->json(['success' => false, 'message' => __('Code invalide ou expire.')], 422);
        }

        $oldSessionId = session()->getId();
        auth()->login($user, true);

        // Synchroniser le panier guest → user
        if (class_exists(\Modules\Shop\Services\CartService::class)) {
            app(\Modules\Shop\Services\CartService::class)->syncSessionCart($oldSessionId, $user->id);
        }

        return response()->json(['success' => true, 'message' => __('Connecté avec succès !')]);
    }
}
