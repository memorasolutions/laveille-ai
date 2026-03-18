<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\AI\Models\AiConversation;
use Modules\Core\Notifications\TemplatedNotification;

class HumanTakeoverNotification extends TemplatedNotification
{
    public function __construct(public AiConversation $conversation) {}

    protected function getTemplateSlug(): string
    {
        return 'ai_human_takeover';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'conversation' => [
                'title' => $this->conversation->title,
                'user_name' => $this->conversation->user?->name ?? __('Visiteur'),
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $userName = $this->conversation->user?->name ?? __('Visiteur');

        return (new MailMessage)
            ->subject(__("Nouvelle demande d'agent"))
            ->line(__(':user a demande une prise en charge humaine pour la conversation : :title', [
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
