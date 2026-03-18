<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class SystemAlertNotification extends TemplatedNotification
{
    public function __construct(
        public string $level,
        public string $message,
    ) {}

    /** @return array<int, string> */
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

    protected function getTemplateSlug(): string
    {
        return 'system_alert';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'alert_message' => $this->message,
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
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

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'level' => $this->level,
            'message' => $this->message,
        ];
    }
}
