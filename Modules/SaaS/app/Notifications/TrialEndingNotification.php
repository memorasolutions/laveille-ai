<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class TrialEndingNotification extends TemplatedNotification
{
    public function __construct(private readonly string $trialEndsAt) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'saas_trial_ending';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'trial' => ['ends_at' => $this->trialEndsAt],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Fin de la période d'essai")
            ->line("Votre période d'essai se termine le {$this->trialEndsAt}.")
            ->line('Souscrivez à un abonnement pour continuer à profiter de toutes les fonctionnalités.')
            ->action('Voir les plans', url('/pricing'));
    }
}
