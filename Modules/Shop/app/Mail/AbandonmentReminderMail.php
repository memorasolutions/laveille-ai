<?php

namespace Modules\Shop\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Shop\Models\Order;

class AbandonmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $variant,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->variant) {
            '24h' => 'Votre panier vous attend – rabais de bienvenue',
            '72h' => 'Dernier rappel : vos produits s\'impatientent',
            default => 'Votre commande est toujours en attente',
        };

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.abandonment-reminder',
            with: [
                'order' => $this->order,
                'variant' => $this->variant,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
