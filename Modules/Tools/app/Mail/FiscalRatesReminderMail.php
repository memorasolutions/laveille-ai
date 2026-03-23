<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FiscalRatesReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $currentYear,
        public mixed $configYear,
        public string $lastUpdated,
        public string $jsonPath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[La veille] Rappel — vérifier les taux fiscaux {$this->currentYear}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'tools::emails.fiscal-rates-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
