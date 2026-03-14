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
        $service = app(EmailTemplateService::class);
        $rendered = $service->render('welcome', [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
        ]);

        if ($rendered) {
            return (new MailMessage)
                ->subject($rendered['subject'])
                ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
        }

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
