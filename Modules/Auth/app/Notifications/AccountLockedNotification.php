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

class AccountLockedNotification extends TemplatedNotification
{
    public function __construct(
        private readonly int $lockoutMinutes,
        private readonly string $ipAddress
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'auth_account_locked';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'lockout' => [
                'minutes' => $this->lockoutMinutes,
                'ip_address' => $this->ipAddress,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Alerte de securite : votre compte a ete verrouille'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Nous avons detecte plusieurs tentatives de connexion echouees sur votre compte.'))
            ->line(__('Par mesure de securite, votre compte a ete verrouille pour :minutes minute(s).', [
                'minutes' => $this->lockoutMinutes,
            ]))
            ->line(__('Adresse IP : :ip', ['ip' => $this->ipAddress]))
            ->line(__('Si ce n\'etait pas vous, nous vous recommandons de changer votre mot de passe des que possible.'))
            ->action(__('Reinitialiser mon mot de passe'), route('password.request'))
            ->line(__('Si c\'etait vous, vous pouvez ignorer cet email. Votre compte sera deverrouille automatiquement.'))
            ->salutation(__('L\'equipe :app', ['app' => config('app.name')]));
    }
}
