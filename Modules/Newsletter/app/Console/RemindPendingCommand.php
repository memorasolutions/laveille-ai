<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;
use Throwable;

class RemindPendingCommand extends Command
{
    protected $signature = 'newsletter:remind-pending {--dry-run : Aperçu sans envoi}';

    protected $description = 'Renvoie un email de confirmation aux abonnés non confirmés depuis 24h-48h (J+1)';

    public function handle(): int
    {
        $subscribers = Subscriber::whereNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->where('created_at', '<=', now()->subDay())
            ->where('created_at', '>', now()->subDays(2))
            ->get();

        if ($this->option('dry-run')) {
            foreach ($subscribers as $subscriber) {
                $this->line($subscriber->email);
            }

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Notification::route('mail', $subscriber->email)
                    ->notify(new WelcomeNewsletterNotification($subscriber));
                $ok++;
            } catch (Throwable $e) {
                Log::error('Failed to send reminder to '.$subscriber->email, [
                    'exception' => $e,
                ]);
                $fail++;
            }
        }

        Log::info("Reminder J+1 envoyé : {$ok} OK, {$fail} fail");
        $this->info("Reminder J+1 envoyé : {$ok} OK, {$fail} fail");

        return self::SUCCESS;
    }
}
