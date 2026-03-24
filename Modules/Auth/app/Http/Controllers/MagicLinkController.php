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
use Illuminate\Support\Facades\RateLimiter;
use Modules\Auth\Notifications\MagicLinkNotification;
use Modules\Auth\Services\MagicLinkService;
use Modules\Notifications\Contracts\SmsDriverInterface;
use Modules\Settings\Facades\Settings;

class MagicLinkController extends Controller
{
    public function __construct(private readonly MagicLinkService $magicLink) {}

    public function showRequestForm(): View
    {
        if (view()->exists('fronttheme::auth.magic-link-request')) {
            return view('fronttheme::auth.magic-link-request');
        }

        return view('auth::livewire.magic-link-request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $rateLimitKey = 'magic-link-email:'.sha1($request->email);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors(['email' => "Trop de tentatives. Réessayez dans {$seconds} secondes."]);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $result = $this->magicLink->generate($request->email);
        $user = User::where('email', $request->email)->first();
        $user?->notify(new MagicLinkNotification($result['token']));

        if (app()->environment('local')) {
            session(['dev_magic_code' => $result['token']]);
        }

        $expiryMinutes = (int) Settings::get('magic_link_expiry_minutes', 15);

        return redirect()->route('magic-link.verify', ['email' => $request->email])
            ->with('status', "Code de connexion envoyé par courriel. Valide {$expiryMinutes} minutes.");
    }

    public function sendSms(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

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

        if (view()->exists('fronttheme::auth.magic-link-verify')) {
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

        auth()->login($user, true);

        if ($user->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended(route('user.dashboard'));
    }

    /**
     * API : envoyer un magic link (JSON, pour usage inline sans redirection).
     */
    public function sendLinkApi(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['success' => false, 'message' => __('Aucun compte avec cette adresse. Inscrivez-vous d abord.')], 422);
        }

        $rateLimitKey = 'magic-link-email:' . sha1($request->email);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json(['success' => false, 'message' => __('Trop de tentatives. Reessayez dans :seconds secondes.', ['seconds' => $seconds])], 429);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $result = $this->magicLink->generate($request->email);
        $user->notify(new MagicLinkNotification($result['token']));

        return response()->json(['success' => true, 'message' => __('Code de connexion envoye par courriel.')]);
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

        auth()->login($user, true);

        return response()->json(['success' => true, 'message' => __('Connecte avec succes !')]);
    }
}
