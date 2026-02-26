<?php

declare(strict_types=1);

namespace Modules\Newsletter\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Newsletter\Models\Subscriber;

class WelcomeNewsletterNotification extends Notification
{
    public function __construct(private readonly Subscriber $subscriber) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $confirmUrl = route('newsletter.confirm', $this->subscriber->token);
        $unsubUrl = route('newsletter.unsubscribe', $this->subscriber->token);

        return (new MailMessage)
            ->subject('Confirmez votre abonnement à la newsletter')
            ->greeting('Bonjour '.($this->subscriber->name ?? '').' !')
            ->line('Merci pour votre abonnement à notre newsletter.')
            ->action('Confirmer mon abonnement', $confirmUrl)
            ->line('Si vous ne souhaitez plus recevoir nos emails, [cliquez ici]('.$unsubUrl.').')
            ->salutation('À bientôt !');
    }
}
