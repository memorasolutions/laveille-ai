<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Models\Subscriber;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->validateSignature($request);

        $payload = $request->all();

        if (! isset($payload['event']) || ! isset($payload['email'])) {
            Log::warning('Brevo webhook: payload invalide', $payload);

            return response()->json(['status' => 'ignored'], 200);
        }

        $event = $payload['event'];
        $email = $payload['email'];

        Log::info("Brevo webhook: {$event} pour {$email}");

        $subscriber = Subscriber::where('email', $email)->first();

        if (! $subscriber) {
            return response()->json(['status' => 'ok'], 200);
        }

        match ($event) {
            'hard_bounce', 'complaint', 'blocked' => $this->handlePermanentFailure($subscriber, $event),
            'soft_bounce' => $this->handleSoftBounce($subscriber),
            'unsubscribed' => $this->handleUnsubscribe($subscriber),
            default => null,
        };

        return response()->json(['status' => 'ok'], 200);
    }

    private function handlePermanentFailure(Subscriber $subscriber, string $reason): void
    {
        if (is_null($subscriber->unsubscribed_at)) {
            $subscriber->update([
                'unsubscribed_at' => now(),
                'bounce_reason' => $reason,
            ]);
        }
    }

    private function handleSoftBounce(Subscriber $subscriber): void
    {
        $subscriber->increment('bounce_count');
        $subscriber->update(['bounce_reason' => 'soft_bounce']);

        if ($subscriber->fresh()->bounce_count >= 3 && is_null($subscriber->unsubscribed_at)) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }
    }

    private function handleUnsubscribe(Subscriber $subscriber): void
    {
        if (is_null($subscriber->unsubscribed_at)) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }
    }

    private function validateSignature(Request $request): void
    {
        $secret = config('services.brevo.webhook_secret');

        if (! $secret) {
            return;
        }

        $signature = $request->header('X-Brevo-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(403, 'Signature webhook Brevo invalide');
        }
    }
}
