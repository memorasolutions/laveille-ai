<?php

declare(strict_types=1);

namespace Modules\Directory\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Mail\Traits\RoutesToWorkspaceMailer;

/**
 * Notifie l'admin d'une demande de retrait (takedown) d'un contenu d'annuaire.
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class ToolTakedownRequestMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(public \Modules\Directory\Models\TakedownRequest $takedown) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[La veille] Demande de retrait — '.($this->takedown->tool?->name ?? 'contenu général'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'directory::emails.takedown-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
