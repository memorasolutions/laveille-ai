<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Modules\Notifications\Models\SentEmail;

class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $to = collect($message->getTo())->map(fn ($address) => $address->getAddress())->implode(', ');

        SentEmail::create([
            'to' => $to,
            'subject' => $message->getSubject(),
            'mailable_class' => $event->data['__laravel_notification'] ?? null,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
