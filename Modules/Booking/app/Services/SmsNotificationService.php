<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Modules\Booking\Models\Appointment;

class SmsNotificationService
{
    public function __construct(protected SmsService $smsService) {}

    public function sendConfirmation(Appointment $appointment): void
    {
        if (! $this->shouldSend($appointment)) {
            return;
        }

        $message = $this->buildMessage(config('booking.sms.templates.confirmation', ''), [
            '{service}' => $appointment->service->name,
            '{date}' => $appointment->start_at->locale('fr')->isoFormat('LL'),
            '{time}' => $appointment->start_at->format('H:i'),
        ]);

        $this->smsService->send(
            $this->formatPhone($appointment->customer->phone),
            $message
        );
    }

    public function sendReminder(Appointment $appointment, int $hoursBefore): void
    {
        if (! $this->shouldSend($appointment)) {
            return;
        }

        $message = $this->buildMessage(config('booking.sms.templates.reminder', ''), [
            '{service}' => $appointment->service->name,
            '{time}' => $appointment->start_at->format('H:i'),
        ]);

        $this->smsService->send(
            $this->formatPhone($appointment->customer->phone),
            $message
        );
    }

    public function sendCancellation(Appointment $appointment): void
    {
        if (! $this->shouldSend($appointment)) {
            return;
        }

        $message = $this->buildMessage(config('booking.sms.templates.cancellation', ''), [
            '{service}' => $appointment->service->name,
            '{date}' => $appointment->start_at->locale('fr')->isoFormat('LL'),
            '{url}' => route('booking.wizard'),
        ]);

        $this->smsService->send(
            $this->formatPhone($appointment->customer->phone),
            $message
        );
    }

    private function shouldSend(Appointment $appointment): bool
    {
        return config('booking.sms.enabled', false)
            && ! empty($appointment->customer->phone);
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        return str_starts_with($phone, '+') ? $phone : '+'.$digits;
    }

    private function buildMessage(string $template, array $data): string
    {
        return str_replace(array_keys($data), array_values($data), $template);
    }
}
