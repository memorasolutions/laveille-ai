<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Booking\Mail\BookingReminder;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Services\SmsNotificationService;
use Modules\Booking\Services\WebhookDispatchService;

class SendBookingReminders extends Command
{
    protected $signature = 'booking:send-reminders';

    protected $description = 'Envoie les rappels de rendez-vous et marque les no-shows';

    public function handle(): int
    {
        $now = Carbon::now();
        $sent24h = $this->sendReminders($now, 24, 'email_24h', '24 heures');
        $sent2h = $this->sendReminders($now, 2, 'email_2h', '2 heures');
        $noShows = $this->processNoShows();

        $this->info("Rappels envoyés : {$sent24h} (24h), {$sent2h} (2h). No-shows : {$noShows}");

        return self::SUCCESS;
    }

    protected function sendReminders(Carbon $now, int $hours, string $tag, string $label): int
    {
        $appointments = Appointment::with(['service', 'customer'])
            ->whereBetween('start_at', [
                $now->copy()->addMinutes($hours * 60 - 15),
                $now->copy()->addMinutes($hours * 60 + 15),
            ])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereJsonDoesntContain('reminders_sent', $tag)
            ->get();

        $smsService = app(SmsNotificationService::class);

        foreach ($appointments as $appointment) {
            Mail::to($appointment->customer->email)
                ->queue(new BookingReminder($appointment, $label));

            $smsService->sendReminder($appointment, $hours);

            $appointment->update([
                'reminders_sent' => array_merge($appointment->reminders_sent ?? [], [$tag]),
            ]);
        }

        return $appointments->count();
    }

    protected function processNoShows(): int
    {
        $appointments = Appointment::where('status', 'confirmed')
            ->where('end_at', '<', now())
            ->get();

        foreach ($appointments as $appointment) {
            $appointment->update(['status' => 'no_show']);

            BookingCustomer::where('id', $appointment->customer_id)
                ->increment('no_show_count');

            app(WebhookDispatchService::class)->dispatchForAppointment('appointment.no_show', $appointment);
        }

        return $appointments->count();
    }
}
