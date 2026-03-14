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
use Modules\AI\Models\AiConversation;

class HumanTakeoverNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AiConversation $conversation) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->conversation->user?->name ?? __('Visiteur');

        return (new MailMessage)
            ->subject(__("Nouvelle demande d'agent"))
            ->line(__(':user a demandé une prise en charge humaine pour la conversation : :title', [
                'user' => $userName,
                'title' => $this->conversation->title,
            ]))
            ->action(__('Voir les conversations'), route('admin.ai.agent.index'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_name' => $this->conversation->user?->name ?? __('Visiteur'),
            'title' => $this->conversation->title,
            'type' => 'handoff_request',
        ];
    }
}
