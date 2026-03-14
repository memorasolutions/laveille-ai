<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $provider;

    protected array $config;

    public function __construct()
    {
        $this->provider = config('booking.sms.provider', 'log');
        $this->config = config('booking.sms', []);
    }

    public function send(string $to, string $message): bool
    {
        if (! config('booking.sms.enabled', false)) {
            return false;
        }

        return match ($this->provider) {
            'vonage' => $this->sendViaVonage($to, $message),
            'twilio' => $this->sendViaTwilio($to, $message),
            default => $this->sendViaLog($to, $message),
        };
    }

    private function sendViaVonage(string $to, string $message): bool
    {
        try {
            $cfg = $this->config['vonage'] ?? [];
            $basic = new \Vonage\Client\Credentials\Basic($cfg['api_key'] ?? '', $cfg['api_secret'] ?? '');
            $client = new \Vonage\Client($basic);

            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($to, $this->config['from'] ?? '', $message)
            );

            return $response->current()->getStatus() == 0;
        } catch (\Throwable $e) {
            Log::error('Vonage SMS failed: '.$e->getMessage());

            return false;
        }
    }

    private function sendViaTwilio(string $to, string $message): bool
    {
        try {
            $cfg = $this->config['twilio'] ?? [];
            $client = new \Twilio\Rest\Client($cfg['sid'] ?? '', $cfg['token'] ?? '');
            $client->messages->create($to, [
                'from' => $cfg['from'] ?? $this->config['from'] ?? '',
                'body' => $message,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio SMS failed: '.$e->getMessage());

            return false;
        }
    }

    private function sendViaLog(string $to, string $message): bool
    {
        Log::info("SMS [{$this->provider}] → {$to}: {$message}");

        return true;
    }
}
