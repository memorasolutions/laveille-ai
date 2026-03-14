<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Adapters;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Modules\AI\Contracts\ChannelAdapterInterface;
use Modules\AI\Enums\TicketPriority;
use Modules\AI\Enums\TicketStatus;
use Modules\AI\Events\ChannelMessageReceived;
use Modules\AI\Models\Channel;
use Modules\AI\Models\ChannelMessage;
use Modules\AI\Models\Ticket;

class EmailChannelAdapter implements ChannelAdapterInterface
{
    public function send(array $message, Channel $channel): array
    {
        $to = $message['to'] ?? null;
        $subject = $message['subject'] ?? '';
        $body = $message['body'] ?? '';

        if ($to) {
            Mail::raw($body, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });
        }

        return ['status' => 'sent'];
    }

    public function receive(array $payload, Channel $channel): ?ChannelMessage
    {
        $from = $payload['from'] ?? '';
        $subject = $payload['subject'] ?? '';
        $body = $payload['body'] ?? '';
        $messageId = $payload['message_id'] ?? uniqid('email_', true);

        $user = User::where('email', $from)->first();

        // Check if this is a reply to an existing ticket
        $ticket = null;
        if (preg_match('/Re:\s*Ticket\s*#(\d+)/i', $subject, $matches)) {
            $ticket = Ticket::find((int) $matches[1]);
        }

        if (! $ticket) {
            $ticket = Ticket::create([
                'title' => $subject ?: __('Email sans sujet'),
                'description' => $body,
                'user_id' => $user?->id,
                'status' => TicketStatus::Open,
                'priority' => TicketPriority::Medium,
            ]);
        }

        $channelMessage = ChannelMessage::create([
            'channel_id' => $channel->id,
            'external_id' => $messageId,
            'direction' => 'inbound',
            'status' => 'received',
            'subject' => $subject,
            'body' => $body,
            'sender' => $from,
            'payload' => $payload,
            'ticket_id' => $ticket->id,
            'occurred_at' => now(),
        ]);

        event(new ChannelMessageReceived($channelMessage, $channel));

        return $channelMessage;
    }
}
