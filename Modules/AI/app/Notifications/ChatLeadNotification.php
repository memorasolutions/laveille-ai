<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class ChatLeadNotification extends TemplatedNotification
{
    public function __construct(
        protected ContactMessage $contactMessage
    ) {}

    protected function getTemplateSlug(): string
    {
        return 'ai_chat_lead';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'contact' => [
                'name' => $this->contactMessage->name,
                'email' => $this->contactMessage->email,
                'subject' => $this->contactMessage->subject,
                'message' => $this->contactMessage->message,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau lead via le chatbot')
            ->greeting('Nouveau lead reçu !')
            ->line('**Nom :** '.$this->contactMessage->name)
            ->line('**Courriel :** '.$this->contactMessage->email)
            ->line('**Sujet :** '.$this->contactMessage->subject)
            ->line('**Message :** '.$this->contactMessage->message)
            ->action('Voir dans le backoffice', url('/admin/contact-messages'))
            ->salutation('L\'équipe '.config('app.name'));
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
