<?php

declare(strict_types=1);

namespace Modules\Authors\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;

class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuthorSubscriber $subscriber,
        public AuthorProfile $author
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $replyToAddress = (string) ($this->author->email ?? $fromAddress);

        return new Envelope(
            subject: 'Confirme ton abonnement à la newsletter de '.($this->author->display_name ?? 'La veille de Stef'),
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($replyToAddress)],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'authors::mail.newsletter-confirmation',
            with: [
                'confirmUrl' => $this->confirmUrl(),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
        );
    }

    public function confirmUrl(): string
    {
        return URL::signedRoute(
            'authors.newsletter.confirm',
            [
                'slug' => $this->author->slug,
                'token' => $this->subscriber->confirmation_token,
            ],
            now()->addDays(7)
        );
    }

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute(
            'authors.newsletter.unsubscribe',
            [
                'slug' => $this->author->slug,
                'token' => $this->subscriber->confirmation_token,
            ]
        );
    }
}
