<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Services\BrevoService;

class SendDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [
            $this->jitter(30),
            $this->jitter(90),
            $this->jitter(300),
        ];
    }

    private function jitter(int $seconds): int
    {
        return $seconds + random_int(0, 30);
    }

    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly string $subject,
        public readonly string $htmlContent,
        public readonly int $issueId,
        public readonly string $subscriberToken,
    ) {
        $this->onQueue('newsletters');
    }

    public function handle(BrevoService $brevoService): void
    {
        if ($this->alreadySent()) {
            return;
        }

        $html = str_replace(
            '{{UNSUBSCRIBE_URL}}',
            route('newsletter.unsubscribe', ['token' => $this->subscriberToken]),
            $this->htmlContent
        );

        $result = $brevoService->sendCampaignEmail(
            $this->email,
            $this->name,
            $this->subject,
            $html
        );

        if ($result['success']) {
            $this->markAsSent();
        } else {
            Log::warning("Digest envoi echoue pour {$this->email}", [
                'issue_id' => $this->issueId,
                'error' => $result['error'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendDigestJob echoue apres {$this->tries} tentatives", [
            'issue_id' => $this->issueId,
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }

    private function alreadySent(): bool
    {
        return DB::table('newsletter_issue_sends')
            ->where('issue_id', $this->issueId)
            ->where('subscriber_email', $this->email)
            ->exists();
    }

    private function markAsSent(): void
    {
        $now = Carbon::now();

        DB::table('newsletter_issue_sends')->insertOrIgnore([
            'issue_id' => $this->issueId,
            'subscriber_email' => $this->email,
            'sent_at' => $now,
            'created_at' => $now,
        ]);
    }
}
