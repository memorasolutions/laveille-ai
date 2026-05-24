<?php

declare(strict_types=1);

namespace Modules\Authors\Services\Newsletter;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Modules\Authors\Contracts\NewsletterProvider;

final class BrevoProvider implements NewsletterProvider
{
    private string $apiKey;

    public function __construct(string $encryptedApiKey)
    {
        try {
            $this->apiKey = Crypt::decryptString($encryptedApiKey);
        } catch (\Exception $e) {
            $this->apiKey = '';
        }
    }

    public function connect(array $credentials): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            return Http::withHeaders(['api-key' => $this->apiKey])
                ->timeout(10)
                ->get('https://api.brevo.com/v3/account')
                ->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function disconnect(): bool
    {
        $this->apiKey = '';
        return true;
    }

    public function listAudiences(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $response = Http::withHeaders(['api-key' => $this->apiKey])
                ->timeout(10)
                ->get('https://api.brevo.com/v3/contacts/lists');

            if (! $response->successful()) {
                return [];
            }

            return array_map(fn ($list) => [
                'id' => Arr::get($list, 'id', ''),
                'name' => Arr::get($list, 'name', ''),
                'subscribers_count' => Arr::get($list, 'totalSubscribers', 0),
            ], (array) $response->json('lists', []));
        } catch (\Exception $e) {
            return [];
        }
    }

    public function createCampaign(string $title, string $htmlContent, ?string $audienceId = null): string
    {
        if (empty($this->apiKey)) {
            return '';
        }

        try {
            $sender = (array) config('newsletter.brevo.sender', [
                'name' => config('app.name', 'Memora'),
                'email' => config('mail.from.address', 'noreply@laveille.ai'),
            ]);

            $payload = [
                'name' => $title,
                'subject' => $title,
                'htmlContent' => $htmlContent,
                'sender' => $sender,
                'type' => 'classic',
            ];

            if ($audienceId) {
                $payload['recipients'] = ['listIds' => [(int) $audienceId]];
            }

            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://api.brevo.com/v3/emailCampaigns', $payload);

            return $response->successful() ? (string) $response->json('id', '') : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function sendCampaign(string $campaignId): bool
    {
        if (empty($this->apiKey) || empty($campaignId)) {
            return false;
        }

        try {
            return Http::withHeaders(['api-key' => $this->apiKey])
                ->timeout(10)
                ->post("https://api.brevo.com/v3/emailCampaigns/{$campaignId}/sendNow")
                ->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function healthCheck(): bool
    {
        return $this->connect([]);
    }

    public function getProviderName(): string
    {
        return 'Brevo';
    }
}
