<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Services\EmailTemplateService;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $level,
        public string $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'system_alert',
            'level' => $this->level,
            'message' => $this->message,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = app(EmailTemplateService::class);
        $rendered = $service->render('system_alert', [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'alert_message' => $this->message,
        ]);

        if ($rendered) {
            return (new MailMessage)
                ->subject($rendered['subject'])
                ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
        }

        $greeting = match ($this->level) {
            'info' => 'Information système',
            'warning' => 'Avertissement système',
            'critical' => 'Alerte critique',
            default => 'Alerte système',
        };

        return (new MailMessage)
            ->subject('Alerte système - '.config('app.name'))
            ->greeting($greeting)
            ->line($this->message)
            ->action('Accéder à votre espace', url('/admin'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'level' => $this->level,
            'message' => $this->message,
        ];
    }
}
