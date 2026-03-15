<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Privacy\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Modules\Privacy\Models\UserConsent;

class ConsentController extends Controller
{
    /**
     * POST /api/privacy/consent - Enregistre un nouveau consentement (art. 7 RGPD)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'choices.essential' => 'required|boolean',
            'choices.analytics' => 'boolean',
            'choices.marketing' => 'boolean',
            'choices.personalization' => 'boolean',
            'choices.third_party' => 'boolean',
            'jurisdiction' => 'required|string|in:gdpr,canada_quebec,pipeda,ccpa',
            'policy_version' => 'required|string|max:20',
        ]);

        $choices = $validated['choices'];
        $choices['essential'] = true; // Toujours actif
        $jurisdiction = $validated['jurisdiction'];

        // Respect du signal GPC (Global Privacy Control)
        $gpcEnabled = $request->header(config('privacy.gpc.header', 'Sec-GPC')) === '1';
        if ($gpcEnabled && in_array($jurisdiction, config('privacy.gpc.respect_in', []))) {
            $choices['marketing'] = false;
            $choices['third_party'] = false;
        }

        $expirationDays = config("privacy.consent.expiration.{$jurisdiction}", 365);

        $consent = UserConsent::create([
            'ip_hash' => hash('sha256', $request->ip()),
            'user_agent' => $request->userAgent(),
            'choices' => $choices,
            'jurisdiction' => $jurisdiction,
            'policy_version' => $validated['policy_version'],
            'region_detected' => $request->header('CF-IPCountry'),
            'gpc_enabled' => $gpcEnabled,
            'expires_at' => now()->addDays($expirationDays),
        ]);

        // Cookie consent_v1 accessible par JS (httpOnly: false)
        $cookieValue = json_encode([
            'token' => $consent->consent_token,
            'choices' => $choices,
            'v' => $validated['policy_version'],
        ]);

        $cookie = Cookie::make(
            config('privacy.consent.cookie_name', 'consent_v1'),
            $cookieValue,
            $expirationDays * 1440,
            '/',
            null,
            true,  // secure
            false, // httpOnly false pour acces JS
            false,
            'Lax'
        );

        return response()->json([
            'success' => true,
            'token' => $consent->consent_token,
            'expires_at' => $consent->expires_at->toIso8601String(),
        ], 201)->cookie($cookie);
    }

    /**
     * GET /api/privacy/consent/{token} - Recupere un consentement existant
     */
    public function show(string $token): JsonResponse
    {
        $consent = UserConsent::where('consent_token', $token)
            ->active()
            ->firstOrFail();

        return response()->json([
            'choices' => $consent->choices,
            'jurisdiction' => $consent->jurisdiction,
            'policy_version' => $consent->policy_version,
            'expires_at' => $consent->expires_at->toIso8601String(),
        ]);
    }

    /**
     * GET /api/privacy/cookie-list - Liste des cookies par categorie
     */
    public function cookieList(): JsonResponse
    {
        return response()->json(config('privacy.categories'));
    }
}
