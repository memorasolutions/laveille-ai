<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Mail\AbandonmentReminderMail;
use Modules\Shop\Models\Order;

class SendAbandonmentRemindersCommand extends Command
{
    protected $signature = 'shop:send-abandonment-reminders {--force : Bypass kill switch}';

    protected $description = 'Envoie rappels 24h et 72h pour commandes pending non payées';

    public function handle(): int
    {
        if (! class_exists(Order::class)) {
            $this->warn('Module Shop introuvable — skip.');
            return self::SUCCESS;
        }

        if (class_exists(\Laravel\Pennant\Feature::class)) {
            if (! \Laravel\Pennant\Feature::active('cron.cart-abandonment') && ! $this->option('force')) {
                $this->components->warn('Kill switch cron.cart-abandonment actif.');
                return self::SUCCESS;
            }
        }

        $sent24h = 0;
        $sent72h = 0;
        $errors = 0;

        $orders24h = Order::query()
            ->where('status', 'pending')
            ->whereBetween('created_at', [now()->subHours(26), now()->subHours(24)])
            ->whereNull('abandonment_reminder_24h_sent_at')
            ->whereNotNull('email')
            ->get();

        foreach ($orders24h as $order) {
            try {
                Mail::to($order->email)->send(new AbandonmentReminderMail($order, '24h'));
                $order->update(['abandonment_reminder_24h_sent_at' => now()]);
                $sent24h++;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Abandonment reminder 24h failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'email' => $order->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $orders72h = Order::query()
            ->where('status', 'pending')
            ->whereBetween('created_at', [now()->subHours(74), now()->subHours(72)])
            ->whereNull('abandonment_reminder_72h_sent_at')
            ->whereNotNull('abandonment_reminder_24h_sent_at')
            ->whereNotNull('email')
            ->get();

        foreach ($orders72h as $order) {
            try {
                Mail::to($order->email)->send(new AbandonmentReminderMail($order, '72h'));
                $order->update(['abandonment_reminder_72h_sent_at' => now()]);
                $sent72h++;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Abandonment reminder 72h failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'email' => $order->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Rappels d'abandon envoyés — 24h: {$sent24h}, 72h: {$sent72h}, erreurs: {$errors}");

        if ($sent24h > 0 || $sent72h > 0 || $errors > 0) {
            Log::info('SendAbandonmentReminders terminé', [
                'sent_24h' => $sent24h,
                'sent_72h' => $sent72h,
                'errors' => $errors,
            ]);
        }

        return self::SUCCESS;
    }
}
