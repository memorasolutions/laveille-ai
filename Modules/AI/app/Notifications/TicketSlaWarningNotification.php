<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\AI\Models\Ticket;
use Modules\Core\Notifications\TemplatedNotification;

class TicketSlaWarningNotification extends TemplatedNotification
{
    public function __construct(public Ticket $ticket) {}

    protected function getTemplateSlug(): string
    {
        return 'ai_ticket_sla_warning';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'ticket' => [
                'title' => $this->ticket->title,
                'due_at' => $this->ticket->due_at?->format('d/m/Y H:i'),
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Alerte SLA : ').$this->ticket->title)
            ->line(__('Ticket : ').$this->ticket->title)
            ->line(__('Echeance : ').$this->ticket->due_at?->format('d/m/Y H:i'))
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
