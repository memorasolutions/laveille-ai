<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ShortUrl\Models\ShortUrl;

class ShortUrlExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(public ShortUrl $shortUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre lien court a expiré')
            ->greeting('Bonjour !')
            ->line('Le lien court **' . $this->shortUrl->slug . '** pointant vers :')
            ->line('`' . $this->shortUrl->original_url . '`')
            ->line('a expiré et a été supprimé automatiquement après 12 mois d\'inactivité.')
            ->line('Dernière visite : ' . ($this->shortUrl->last_visited_at?->format('d/m/Y') ?? 'Jamais'))
            ->line('Vous pouvez créer un nouveau lien court à tout moment.')
            ->action('Créer un nouveau lien', url('/raccourcir'));
    }
}
