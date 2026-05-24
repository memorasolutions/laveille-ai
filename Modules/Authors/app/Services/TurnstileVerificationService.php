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
                Log::channel('daily')->warning('turnstile.verify.http_error', ['status' => $response->status()]);

                return false;
            }

            $data = $response->json();

            if (($data['success'] ?? false) !== true) {
                Log::channel('daily')->info('turnstile.verify.failed', ['errors' => $data['error-codes'] ?? []]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('daily')->error('turnstile.verify.exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }
}
