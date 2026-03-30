<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Directory\Models\Tool;

final class ToolSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Tool $tool,
        private readonly User $submitter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $adminUrl = URL::to('/admin/directory/'.$this->tool->getKey().'/edit');

        return (new MailMessage)
            ->subject('[laveille.ai] Nouvel outil soumis : '.$this->tool->name)
            ->line('Un nouvel outil a été soumis via le répertoire.')
            ->line('Nom : '.$this->tool->name)
            ->line('URL : '.($this->tool->url ?: 'N/A'))
            ->line('Tarification : '.($this->tool->pricing ?: 'N/A'))
            ->line('Soumis par : '.$this->submitter->name.' ('.$this->submitter->email.')')
            ->action('Voir dans l\'admin', $adminUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tool_submitted',
            'tool_id' => $this->tool->getKey(),
            'tool_name' => (string) $this->tool->name,
            'tool_url' => (string) $this->tool->url,
            'submitter_name' => (string) $this->submitter->name,
            'submitter_email' => (string) $this->submitter->email,
            'message' => 'Nouvel outil soumis : '.$this->tool->name.' par '.$this->submitter->name,
        ];
    }
}
