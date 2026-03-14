<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LockoutContactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $userEmail,
        private readonly string $userMessage,
        private readonly string $ipAddress
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Alerte : utilisateur verrouillé demande assistance'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Un utilisateur verrouillé a envoyé une demande d\'assistance.'))
            ->line(__('Courriel : :email', ['email' => $this->userEmail]))
            ->line(__('Adresse IP : :ip', ['ip' => $this->ipAddress]))
            ->line(__('Message :'))
            ->line($this->userMessage)
            ->action(__('Gérer les utilisateurs'), url('/admin/users'))
            ->salutation(__('L\'équipe :app', ['app' => config('app.name')]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lockout_contact',
            'user_email' => $this->userEmail,
            'ip_address' => $this->ipAddress,
            'message' => $this->userMessage,
        ];
    }
}
