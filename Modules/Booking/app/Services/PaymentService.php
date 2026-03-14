<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\Coupon;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('booking.stripe.secret_key', ''));
    }

    public function createCheckoutSession(Appointment $appointment, ?Coupon $coupon = null): string
    {
        $priceInCents = $this->calculatePrice($appointment, $coupon);

        $session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => config('booking.stripe.currency', 'cad'),
                    'product_data' => [
                        'name' => $appointment->service->name,
                    ],
                    'unit_amount' => $priceInCents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('booking.wizard').'?success=1&appointment='.$appointment->id,
            'cancel_url' => route('booking.wizard').'?cancelled=1',
            'customer_email' => $appointment->customer->email,
            'metadata' => [
                'appointment_id' => (string) $appointment->id,
            ],
        ]);

        $appointment->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('booking.stripe.webhook_secret', '')
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe booking webhook signature failed: '.$e->getMessage());
            throw $e;
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCompleted($event->data->object),
            'checkout.session.expired' => $this->handleExpired($event->data->object),
            default => Log::info('Unhandled Stripe booking event: '.$event->type),
        };
    }

    public function isPaymentRequired(): bool
    {
        return (bool) config('booking.stripe.enabled', false);
    }

    protected function calculatePrice(Appointment $appointment, ?Coupon $coupon): int
    {
        $price = (int) round($appointment->service->price * 100);

        if ($coupon) {
            $discountCents = (int) round($coupon->calculateDiscount((float) $appointment->service->price) * 100);
            $price = max(0, $price - $discountCents);

            $appointment->update([
                'coupon_id' => $coupon->id,
                'discount_amount' => $discountCents / 100,
            ]);
        }

        return $price;
    }

    protected function handleCompleted(object $session): void
    {
        $appointment = Appointment::where('stripe_session_id', $session->id)->first();

        if (! $appointment) {
            Log::error('Booking appointment not found for Stripe session: '.$session->id);

            return;
        }

        $appointment->update([
            'payment_status' => 'paid',
            'amount_paid' => $session->amount_total / 100,
        ]);
    }

    protected function handleExpired(object $session): void
    {
        $appointment = Appointment::where('stripe_session_id', $session->id)->first();

        if (! $appointment) {
            return;
        }

        $appointment->update(['payment_status' => 'failed']);
    }
}
