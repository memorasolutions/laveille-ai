<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ShortUrl\Models\ShortUrl;

class ShortUrlExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public ShortUrl $shortUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft = (int) now()->diffInDays($this->shortUrl->expires_at);

        return (new MailMessage)
            ->subject('Votre lien court expire dans ' . $daysLeft . ' jours')
            ->greeting('Bonjour !')
            ->line('Le lien court **' . $this->shortUrl->slug . '** pointant vers :')
            ->line('`' . $this->shortUrl->original_url . '`')
            ->line('expire dans **' . $daysLeft . ' jours**.')
            ->line('Vous pouvez le prolonger depuis votre tableau de bord, section « Mes liens courts ».')
            ->action('Gérer mes liens', url('/user/liens'))
            ->line('Si vous ne faites rien, le lien sera automatiquement supprimé à l\'expiration.');
    }
}
