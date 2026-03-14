<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\AI\Models\Ticket;

class TicketSlaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Alerte SLA : ') . $this->ticket->title)
            ->line(__('Ticket : ') . $this->ticket->title)
            ->line(__('Échéance : ') . $this->ticket->due_at?->format('d/m/Y H:i'))
            ->action(__('Voir le ticket'), route('admin.ai.tickets.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'due_at' => $this->ticket->due_at?->toISOString(),
            'type' => 'ticket_sla_warning',
        ];
    }
}
