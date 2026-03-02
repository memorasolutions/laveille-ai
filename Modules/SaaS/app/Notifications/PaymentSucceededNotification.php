<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $invoiceId,
        private readonly int $amountCents = 0,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $amount = $this->amountCents > 0
            ? number_format($this->amountCents / 100, 2).' $'
            : '';

        $message = (new MailMessage)
            ->subject('Paiement confirmé')
            ->line('Votre paiement a été traité avec succès.');

        if ($amount) {
            $message->line("Montant : {$amount} (facture #{$this->invoiceId}).");
        } else {
            $message->line("Facture : #{$this->invoiceId}.");
        }

        return $message
            ->line('Merci pour votre confiance.')
            ->action('Voir mon abonnement', url('/user/subscription'));
    }
}
