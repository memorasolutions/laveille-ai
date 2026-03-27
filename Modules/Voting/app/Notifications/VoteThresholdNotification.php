<?php

declare(strict_types=1);

namespace Modules\Voting\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VoteThresholdNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $votesCount,
        private string $contentTitle,
        private string $contentUrl,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Votre contenu a atteint {$this->votesCount} votes !")
            ->greeting('Bonjour !')
            ->line("Félicitations ! Votre contenu « {$this->contentTitle} » a atteint {$this->votesCount} votes.")
            ->line("C'est une excellente nouvelle qui témoigne de l'intérêt que suscite votre travail.")
            ->action('Voir mon contenu', $this->contentUrl)
            ->salutation('Cordialement,');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'vote_threshold',
            'message' => "Votre contenu « {$this->contentTitle} » a atteint {$this->votesCount} votes.",
            'votes_count' => $this->votesCount,
            'content_title' => $this->contentTitle,
            'content_url' => $this->contentUrl,
        ];
    }
}
