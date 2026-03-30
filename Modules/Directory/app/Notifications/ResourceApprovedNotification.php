<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Directory\Models\ToolResource;

class ResourceApprovedNotification extends TemplatedNotification
{
    public function __construct(protected ToolResource $resource) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function getTemplateSlug(): string
    {
        return 'resource_approved';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'resource' => [
                'tool_name' => $this->resource->tool?->name ?? '',
                'title' => $this->resource->title,
                'type' => $this->resource->type,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $toolName = $this->resource->tool?->name ?? '';

        return (new MailMessage)
            ->subject(__('Votre ressource a été approuvée'))
            ->greeting(__('Bonjour :name !', ['name' => $notifiable->name]))
            ->line(__("Votre ressource « :title » pour l'outil « :tool » a été approuvée.", [
                'title' => $this->resource->title,
                'tool' => $toolName,
            ]))
            ->action(__('Voir mes contributions'), route('user.contributions'))
            ->line(__('Merci pour votre contribution !'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resource_approved',
            'resource_id' => $this->resource->id,
            'tool_name' => $this->resource->tool?->name ?? '',
            'title' => $this->resource->title,
            'message' => __('Votre ressource « :title » a été approuvée.', [
                'title' => $this->resource->title,
            ]),
        ];
    }
}
