<?php

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Services\EmailTemplateService;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = app(EmailTemplateService::class);
        $rendered = $service->render('magic_link', [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'token' => $this->token,
            'expire_minutes' => '15',
        ]);

        if ($rendered) {
            return (new MailMessage)
                ->subject($rendered['subject'])
                ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
        }

        return (new MailMessage)
            ->subject('Votre code de connexion')
            ->greeting('Bonjour !')
            ->line('Votre code de connexion est :')
            ->line('**'.$this->token.'**')
            ->line('Ce code expire dans 15 minutes.')
            ->line('Si vous n\'avez pas demandé ce code, ignorez cet email.')
            ->salutation('L\'équipe');
    }
}
