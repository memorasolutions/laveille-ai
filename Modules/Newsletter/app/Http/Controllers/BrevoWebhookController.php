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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Models\NewsletterEvent;
use Modules\Newsletter\Models\Subscriber;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret): JsonResponse
    {
        // Auth par secret d'URL (le secret est un segment du chemin, comme ai/webhooks/email/{secret}).
        $expected = config('services.brevo.webhook_secret');
        if (! $expected || ! hash_equals((string) $expected, $secret)) {
            abort(403);
        }

        try {
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

            // Clé d'idempotence stable : un webhook rejoué par Brevo ne crée jamais de doublon.
            $eventKey = sha1(implode('|', [
                'brevo',
                (string) ($payload['message-id'] ?? ''),
                $event,
                $email,
                (string) ($payload['link'] ?? ''),
                (string) ($payload['ts'] ?? $payload['date'] ?? ''),
            ]));

            $occurredAt = isset($payload['ts'])
                ? Carbon::createFromTimestamp((int) $payload['ts'])
                : null;

            $record = NewsletterEvent::firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'event_key' => $eventKey,
                    'email' => $email,
                    'subscriber_id' => $subscriber->id,
                    'event' => $event,
                    'message_id' => $payload['message-id'] ?? null,
                    'link' => $payload['link'] ?? null, // Brevo n'envoie 'link' que sur un clic (event 'click'/'clicked') → on le garde tel quel.
                    'ip' => $request->ip(),
                    'metadata' => $payload,
                    'occurred_at' => $occurredAt,
                ]
            );

            // Effets de bord (maj abonné) UNIQUEMENT si l'événement vient d'être créé (idempotent).
            if ($record->wasRecentlyCreated) {
                match ($event) {
                    'hard_bounce', 'complaint', 'blocked' => $this->handlePermanentFailure($subscriber, $event),
                    'soft_bounce' => $this->handleSoftBounce($subscriber),
                    'unsubscribed' => $this->handleUnsubscribe($subscriber),
                    default => null,
                };
            }

            return response()->json(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur dans le webhook Brevo', [
                'exception' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            // Jamais de 500 renvoyé à Brevo (sinon il rejoue en boucle).
            return response()->json(['status' => 'error'], 200);
        }
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
}
