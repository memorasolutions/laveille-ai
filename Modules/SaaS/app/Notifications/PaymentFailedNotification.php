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

class PaymentFailedNotification extends TemplatedNotification
{
    public function __construct(private readonly string $invoiceId) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'saas_payment_failed';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'invoice' => ['id' => $this->invoiceId],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Échec de paiement')
            ->line("Le paiement de la facture #{$this->invoiceId} a échoué.")
            ->line('Veuillez mettre à jour vos informations de paiement.')
            ->action('Gérer mon abonnement', url('/user/subscription'));
    }
}
