<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue')
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('Bienvenue sur '.config('app.name'))
            ->action('Accéder au tableau de bord', url('/admin'))
            ->line('Merci!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Bienvenue sur '.config('app.name'),
        ];
    }
}
