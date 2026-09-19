<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TurnstileVerificationService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const TIMEOUT_SECONDS = 5;

    public function __construct()
    {
    }

    public function isEnabled(): bool
    {
        return ! empty(config('services.turnstile.secret_key'));
    }

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->asForm()
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]));

            if (! $response->successful()) {
                // Indisponibilité du service (Cloudflare en panne, HTTP en erreur), PAS un jeton
                // rejeté : on laisse passer plutôt que de bloquer un visiteur légitime pour une
                // panne qui n'est pas la sienne. Ne PAS transformer ce true en false : ce serait
                // réintroduire le fail-closed déjà identifié comme le défaut (un anti-spam qui
                // empêche de vendre quand il tombe coûte plus cher que le spam qu'il arrête).
                Log::channel('daily')->warning('turnstile.verify.http_error_laisse_passer', ['status' => $response->status()]);

                return true;
            }

            $data = $response->json();

            if (($data['success'] ?? false) !== true) {
                // Ici Cloudflare A répondu et REFUSE explicitement le jeton : c'est la seule
                // situation où verify() doit retourner false.
                Log::channel('daily')->info('turnstile.verify.failed', ['errors' => $data['error-codes'] ?? []]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // Même logique que le bloc HTTP ci-dessus : timeout ou exception réseau = on n'a pas
            // pu vérifier, ce n'est pas un refus. Laisser passer plutôt que de casser une
            // inscription ou une soumission pour un problème réseau chez nous ou chez Cloudflare.
            Log::channel('daily')->warning('turnstile.verify.exception_laisse_passer', ['error' => $e->getMessage()]);

            return true;
        }
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }
}
