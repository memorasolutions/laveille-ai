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

class TicketAssignedNotification extends TemplatedNotification
{
    public function __construct(public Ticket $ticket) {}

    protected function getTemplateSlug(): string
    {
        return 'ai_ticket_assigned';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'ticket' => [
                'title' => $this->ticket->title,
                'priority' => $this->ticket->priority->value,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Ticket assigné :').$this->ticket->title)
            ->line(__('Un ticket vous a été assigné.'))
            ->line(__('Titre : ').$this->ticket->title)
            ->line(__('Priorité :').__($this->ticket->priority->value))
            ->action(__('Voir le ticket'), route('admin.ai.tickets.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'type' => 'ticket_assigned',
        ];
    }
}
