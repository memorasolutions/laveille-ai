<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class BrevoService
{
    private const API_URL = 'https://api.brevo.com/v3';

    private ?string $apiKey;

    private array $sender;

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key');
        $this->sender = [
            'name' => config('mail.from.name', config('app.name')),
            'email' => config('mail.from.address'),
        ];
    }

    public function sendCampaignEmail(string $to, string $name, string $subject, string $htmlContent): array
    {
        if (! $this->isConfigured()) {
            return $this->errorResponse('Brevo API key is missing.');
        }

        $unsubscribeUrl = $this->getUnsubscribeUrl($to);

        return $this->sendRequest('post', '/smtp/email', [
            'sender' => $this->sender,
            'to' => [['email' => $to, 'name' => $name]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'headers' => [
                'List-Unsubscribe' => "<{$unsubscribeUrl}>",
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        ]);
    }

    public function syncContact(string $email, string $name, array $attributes = []): array
    {
        if (! $this->isConfigured()) {
            return $this->errorResponse('Brevo API key is missing.');
        }

        if (! isset($attributes['FIRSTNAME']) && ! isset($attributes['LASTNAME'])) {
            $parts = explode(' ', $name, 2);
            $attributes['FIRSTNAME'] = $parts[0] ?? '';
            $attributes['LASTNAME'] = $parts[1] ?? '';
        }

        return $this->sendRequest('post', '/contacts', [
            'email' => $email,
            'attributes' => $attributes,
            'updateEnabled' => true,
        ]);
    }

    public function sendBulkCampaign(Collection $subscribers, string $subject, string $htmlContent): array
    {
        if (! $this->isConfigured()) {
            return $this->errorResponse('Brevo API key is missing.');
        }

        if ($subscribers->isEmpty()) {
            return $this->errorResponse('Subscriber list is empty.');
        }

        $successCount = 0;
        $errors = [];

        foreach ($subscribers as $subscriber) {
            $email = $subscriber->email;
            $name = $subscriber->name ?? '';

            $result = $this->sendCampaignEmail($email, (string) $name, $subject, $htmlContent);

            if ($result['success']) {
                $successCount++;
            } else {
                $errors[$email] = $result['error'];
            }
        }

        return [
            'success' => $successCount > 0,
            'message_id' => "bulk_{$successCount}",
            'error' => count($errors) > 0 ? count($errors) . ' envois echoues' : null,
            'stats' => [
                'total' => $subscribers->count(),
                'sent' => $successCount,
                'failed' => count($errors),
            ],
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->sender['email']);
    }

    private function sendRequest(string $method, string $endpoint, array $data): array
    {
        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->$method(self::API_URL . $endpoint, $data);

            if ($response->successful()) {
                $json = $response->json();

                return [
                    'success' => true,
                    'message_id' => $json['messageId'] ?? $json['id'] ?? null,
                    'error' => null,
                ];
            }

            $errorMsg = $response->json('message') ?? $response->body();
            Log::error("Brevo API [{$endpoint}]: {$errorMsg}");

            return $this->errorResponse($errorMsg);
        } catch (\Throwable $e) {
            Log::error('Brevo: ' . $e->getMessage());

            return $this->errorResponse($e->getMessage());
        }
    }

    private function errorResponse(string $message): array
    {
        return ['success' => false, 'message_id' => null, 'error' => $message];
    }

    private function getUnsubscribeUrl(string $email): string
    {
        if (Route::has('newsletter.unsubscribe')) {
            $subscriber = \Modules\Newsletter\Models\Subscriber::where('email', $email)->first();
            if ($subscriber && $subscriber->token) {
                return route('newsletter.unsubscribe', $subscriber->token);
            }
        }

        return url('/');
    }
}
