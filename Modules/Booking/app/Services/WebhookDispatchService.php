<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingWebhook;

class WebhookDispatchService
{
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = BookingWebhook::active()->forEvent($event)->get();

        foreach ($webhooks as $webhook) {
            dispatch(function () use ($webhook, $event, $payload) {
                try {
                    $jsonPayload = json_encode($payload);
                    $signature = hash_hmac('sha256', $jsonPayload, $webhook->secret);

                    $response = Http::withHeaders([
                        'X-Booking-Signature' => $signature,
                        'X-Booking-Event' => $event,
                    ])->post($webhook->url, $payload);

                    $webhook->update([
                        'last_triggered_at' => now(),
                        'last_status' => $response->status(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Booking webhook dispatch failed', [
                        'webhook_id' => $webhook->id,
                        'event' => $event,
                        'error' => $e->getMessage(),
                    ]);

                    $webhook->update([
                        'last_triggered_at' => now(),
                        'last_status' => 0,
                    ]);
                }
            });
        }
    }

    public function dispatchForAppointment(string $event, Appointment $appointment): void
    {
        $appointment->loadMissing(['service', 'customer']);

        $this->dispatch($event, [
            'appointment_id' => $appointment->id,
            'service_name' => $appointment->service?->name,
            'customer_name' => $appointment->customer?->full_name,
            'customer_email' => $appointment->customer?->email,
            'start_at' => $appointment->start_at?->toIso8601String(),
            'end_at' => $appointment->end_at?->toIso8601String(),
            'status' => $appointment->status,
        ]);
    }
}
