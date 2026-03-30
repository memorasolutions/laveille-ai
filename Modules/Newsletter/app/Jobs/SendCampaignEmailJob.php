<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Services\BrevoService;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly string $subject,
        public readonly string $htmlContent,
        public readonly int $campaignId = 0,
    ) {
        $this->onQueue('newsletters');
    }

    public function handle(BrevoService $brevoService): void
    {
        $result = $brevoService->sendCampaignEmail(
            $this->email,
            $this->name,
            $this->subject,
            $this->htmlContent,
        );

        if (! $result['success']) {
            Log::warning("Newsletter envoi echoue pour {$this->email}", [
                'campaign_id' => $this->campaignId,
                'error' => $result['error'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Newsletter job echoue apres {$this->tries} tentatives", [
            'campaign_id' => $this->campaignId,
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
