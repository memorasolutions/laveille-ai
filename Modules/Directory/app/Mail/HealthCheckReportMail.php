<?php

declare(strict_types=1);

namespace Modules\Directory\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Rapport hebdomadaire santé des outils annuaire — admin valide manuellement (pas d'auto-mark).
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class HealthCheckReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $totalChecked,
        public array $stats,
        public array $suspects
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->suspects);
        return new Envelope(
            subject: "[La veille] Health-check annuaire — {$count} outil(s) suspect(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'directory::emails.health-check-report',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
