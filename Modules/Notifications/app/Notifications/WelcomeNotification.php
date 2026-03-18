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

class WelcomeNotification extends TemplatedNotification
{
    protected function getTemplateSlug(): string
    {
        return 'welcome';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue')
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('Bienvenue sur '.config('app.name'))
            ->action('Accéder au tableau de bord', url('/admin'))
            ->line('Merci!');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Bienvenue sur '.config('app.name'),
        ];
    }
}
