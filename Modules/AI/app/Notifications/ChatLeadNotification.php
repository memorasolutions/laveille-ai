<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ContactMessage $contactMessage
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau lead via le chatbot')
            ->greeting('Nouveau lead reçu !')
            ->line('**Nom :** '.$this->contactMessage->name)
            ->line('**Courriel :** '.$this->contactMessage->email)
            ->line('**Sujet :** '.$this->contactMessage->subject)
            ->line('**Message :** '.$this->contactMessage->message)
            ->action('Voir dans le backoffice', url('/admin/contact-messages'))
            ->salutation('L\'équipe MEMORA');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'chat_lead',
            'message' => 'Nouveau lead chatbot de '.$this->contactMessage->name,
            'contact_message_id' => $this->contactMessage->id,
        ];
    }
}
