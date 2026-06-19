<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\ShortUrl\Models\ShortUrl;
use Modules\ShortUrl\Models\ShortUrlDomain;
use Modules\ShortUrl\Services\ShortUrlService;

class QrDynamicLinkController
{
    public function __construct(
        protected ShortUrlService $shortUrlService
    ) {}

    public function store(Request $request): JsonResponse
    {
        // Règles communes (anonyme + connecté)
        $rules = [
            'original_url' => ['required', 'url', 'max:2048'],
            'expires_at'   => ['required', 'date', 'after:now'],
        ];

        // Options réservées aux utilisateurs connectés (miroir du raccourcisseur public)
        if (Auth::check()) {
            $rules += [
                'slug'         => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]+$/', 'min:3', 'max:50',
                                   'unique:short_urls,slug',
                                   'not_in:'.implode(',', ShortUrl::RESERVED_SLUGS)],
                'domain_id'    => ['nullable', 'exists:short_url_domains,id'],
                'password'     => ['nullable', 'string', 'max:255'],
                'max_clicks'   => ['nullable', 'integer', 'min:1'],
                'redirect_type' => ['nullable', 'integer', 'in:301,302'],
            ];
        }

        $validated = $request->validate($rules);

        try {
            $expiresAt = Carbon::parse($validated['expires_at']);

            if (Auth::check()) {
                // Utilisateur connecté : options complètes
                $domainId = $validated['domain_id'] ?? null;
                if ($domainId === null) {
                    $domain = $this->shortUrlService->getDefaultDomain();
                    if ($domain === null) {
                        return response()->json(['message' => 'Aucun domaine court disponible pour le moment.'], 500);
                    }
                    $domainId = $domain->id;
                }

                $slug = $validated['slug'] ?? null;
                if ($slug === null) {
                    $slug = $this->shortUrlService->generateSlug();
                }

                $password = null;
                if (! empty($validated['password'])) {
                    $password = Hash::make($validated['password']);
                }

                $shortUrl = ShortUrl::create([
                    'user_id'        => Auth::id(),
                    'domain_id'      => $domainId,
                    'slug'           => $slug,
                    'original_url'   => $validated['original_url'],
                    'is_active'      => true,
                    'is_anonymous'   => false,
                    'redirect_type'  => $validated['redirect_type'] ?? 302,
                    'expires_at'     => $expiresAt,
                    'password'       => $password,
                    'max_clicks'     => $validated['max_clicks'] ?? null,
                    'auto_extend'    => false, // expiration FIXE : pas de prolongation au scan
                ]);
            } else {
                // Anonyme : slug auto, domaine par défaut
                $domain = $this->shortUrlService->getDefaultDomain();
                if ($domain === null) {
                    return response()->json(['message' => 'Aucun domaine court disponible pour le moment.'], 500);
                }

                $shortUrl = ShortUrl::create([
                    'user_id'       => null,
                    'domain_id'     => $domain->id,
                    'slug'          => $this->shortUrlService->generateSlug(),
                    'original_url'  => $validated['original_url'],
                    'is_active'     => true,
                    'is_anonymous'  => true,
                    'redirect_type' => 302,
                    'expires_at'    => $expiresAt,
                    'auto_extend'   => false, // expiration FIXE
                ]);
            }

            return response()->json([
                'short_url'  => $shortUrl->getShortUrl(),
                'slug'       => $shortUrl->slug,
                'expires_at' => $expiresAt->toISOString(),
                'has_password' => ! empty($validated['password']),
                'max_clicks' => $shortUrl->max_clicks,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur interne est survenue. Réessaie dans quelques instants.'], 500);
        }
    }

    /**
     * Retourne les domaines actifs disponibles pour le sélecteur QR (connectés seulement).
     */
    public function domains(): JsonResponse
    {
        $domains = ShortUrlDomain::where('is_active', true)
            ->where('hidden_in_selector', false)
            ->get(['id', 'domain', 'display_label', 'is_default']);

        return response()->json($domains);
    }
}
