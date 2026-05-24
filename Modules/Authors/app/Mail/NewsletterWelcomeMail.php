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

class NewsletterWelcomeMail extends Mailable implements ShouldQueue
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
        return new Envelope(
            subject: 'Bienvenue dans la newsletter de '.($this->author->display_name ?? $this->author->slug).' 🎉',
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            replyTo: [
                new Address((string) ($this->author->user?->email ?? config('mail.from.address'))),
            ],
        );
    }

    public function headers(): Headers
    {
        $unsubscribeUrl = URL::signedRoute('authors.newsletter.unsubscribe-1click', [
            'slug' => $this->author->slug,
            'token' => $this->subscriber->confirmation_token,
        ]);

        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.$unsubscribeUrl.'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        $unsubscribeUrl = URL::signedRoute('authors.newsletter.unsubscribe-1click', [
            'slug' => $this->author->slug,
            'token' => $this->subscriber->confirmation_token,
        ]);

        return new Content(
            view: 'authors::mail.newsletter-welcome',
            with: ['unsubscribeUrl' => $unsubscribeUrl],
        );
    }
}
