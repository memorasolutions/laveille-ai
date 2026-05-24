<?php

declare(strict_types=1);

namespace Modules\Authors\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Authors\Models\AuthorProfile;

class TipReceivedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuthorProfile $author,
        public int $amountCents,
        public string $currency,
        public ?string $tipperEmail
    ) {
    }

    public function envelope(): Envelope
    {
        $amountFormatted = $this->getAmountFormatted();

        return new Envelope(
            subject: "🎉 Tip reçu de {$amountFormatted} ".strtoupper($this->currency).' !',
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            replyTo: [new Address((string) ($this->tipperEmail ?? config('mail.from.address')))]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'authors::mail.tip-received',
            with: [
                'amountFormatted' => $this->getAmountFormatted(),
            ]
        );
    }

    private function getAmountFormatted(): string
    {
        return number_format($this->amountCents / 100, 2, ',', ' ');
    }
}
