<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Services\EmailTemplateService;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = app(EmailTemplateService::class);
        $rendered = $service->render('password_changed', [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'changed_at' => now()->format('Y-m-d H:i'),
        ]);

        if ($rendered) {
            return (new MailMessage)
                ->subject($rendered['subject'])
                ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
        }

        return (new MailMessage)
            ->subject('Mot de passe modifié')
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('Votre mot de passe a été modifié. Si vous n\'êtes pas à l\'origine de cette action, veuillez sécuriser votre compte immédiatement.')
            ->action('Accéder à votre espace', url('/admin'))
            ->line('Pour des raisons de sécurité, ne partagez jamais votre mot de passe.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'message' => 'Votre mot de passe a été modifié',
            'changed_at' => now()->toISOString(),
        ];
    }
}
