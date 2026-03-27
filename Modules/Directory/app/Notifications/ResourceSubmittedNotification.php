<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Directory\Models\ToolResource;

class ResourceSubmittedNotification extends Notification
{
    public function __construct(protected ToolResource $resource) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $submitter = $this->resource->user->name ?? __('Anonyme');

        return (new MailMessage)
            ->subject(__('Nouvelle ressource soumise'))
            ->greeting(__('Bonjour !'))
            ->line(__('Une nouvelle ressource a été soumise : « :title »', ['title' => $this->resource->title]))
            ->line(__('Type : :type | Soumis par : :name', ['type' => $this->resource->type, 'name' => $submitter]))
            ->action(__('Modérer'), route('admin.directory.moderation'))
            ->line(__('Veuillez examiner cette soumission.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resource_submitted',
            'resource_id' => $this->resource->id,
            'title' => $this->resource->title,
            'url' => $this->resource->url,
            'submitter' => $this->resource->user->name ?? __('Anonyme'),
            'message' => __('Nouvelle ressource soumise : « :title »', ['title' => $this->resource->title]),
        ];
    }
}
