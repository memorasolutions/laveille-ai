<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Contracts\SmsDriverInterface;

class VoipMsService implements SmsDriverInterface
{
    private const API_URL = 'https://voip.ms/api/v1/rest.php';

    public function __construct(
        private string $apiUsername,
        private string $apiPassword,
        private string $didNumber,
    ) {}

    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::timeout(10)->get(self::API_URL, [
                'api_username' => $this->apiUsername,
                'api_password' => $this->apiPassword,
                'method' => 'sendSMS',
                'did' => $this->didNumber,
                'dst' => $this->formatPhone($to),
                'message' => $message,
            ]);

            $data = $response->json();

            if (($data['status'] ?? '') === 'success') {
                Log::info('VoIP.ms SMS sent', ['to' => $to]);

                return true;
            }

            Log::error('VoIP.ms SMS failed', ['to' => $to, 'response' => $data]);

            return false;
        } catch (\Throwable $e) {
            Log::error('VoIP.ms SMS exception', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendBulk(array $recipients, string $message): array
    {
        $results = [];
        foreach ($recipients as $to) {
            $results[$to] = $this->send($to, $message);
        }

        return $results;
    }

    public function getBalance(): ?float
    {
        try {
            $response = Http::timeout(10)->get(self::API_URL, [
                'api_username' => $this->apiUsername,
                'api_password' => $this->apiPassword,
                'method' => 'getBalance',
            ]);

            $data = $response->json();

            if (($data['status'] ?? '') === 'success') {
                return (float) ($data['balance']['current_balance'] ?? 0);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('VoIP.ms balance exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiUsername !== '' && $this->apiPassword !== '' && $this->didNumber !== '';
    }

    /**
     * @return array{success: bool, balance: float|null, error: string|null}
     */
    public function testConnection(): array
    {
        try {
            $balance = $this->getBalance();

            if ($balance !== null) {
                return ['success' => true, 'balance' => $balance, 'error' => null];
            }

            return ['success' => false, 'balance' => null, 'error' => 'Impossible de récupérer le solde'];
        } catch (\Throwable $e) {
            return ['success' => false, 'balance' => null, 'error' => $e->getMessage()];
        }
    }

    public function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return $digits;
    }
}
