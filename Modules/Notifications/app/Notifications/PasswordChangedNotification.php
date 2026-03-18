<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class PasswordChangedNotification extends TemplatedNotification
{
    protected function getTemplateSlug(): string
    {
        return 'password_changed';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'changed_at' => now()->format('Y-m-d H:i'),
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mot de passe modifié')
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('Votre mot de passe a été modifié. Si vous n\'êtes pas à l\'origine de cette action, veuillez sécuriser votre compte immédiatement.')
            ->action('Accéder à votre espace', url('/admin'))
            ->line('Pour des raisons de sécurité, ne partagez jamais votre mot de passe.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'message' => 'Votre mot de passe a été modifié',
            'changed_at' => now()->toISOString(),
        ];
    }
}
