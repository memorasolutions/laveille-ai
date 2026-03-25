<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class LockoutContactNotification extends TemplatedNotification
{
    public function __construct(
        private readonly string $userEmail,
        private readonly string $userMessage,
        private readonly string $ipAddress
    ) {}

    protected function getTemplateSlug(): string
    {
        return 'auth_lockout_contact';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'lockout' => [
                'user_email' => $this->userEmail,
                'ip_address' => $this->ipAddress,
                'message' => $this->userMessage,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Alerte : utilisateur verrouillé demande assistance'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Un utilisateur verrouillé a envoyé une demande d\'assistance.'))
            ->line(__('Courriel : :email', ['email' => $this->userEmail]))
            ->line(__('Adresse IP : :ip', ['ip' => $this->ipAddress]))
            ->line(__('Message :'))
            ->line($this->userMessage)
            ->action(__('Gerer les utilisateurs'), url('/admin/users'))
            ->salutation(__('L\'equipe :app', ['app' => config('app.name')]));
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
