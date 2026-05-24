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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;

class SubscriberDigestMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuthorSubscriber $subscriber,
        public AuthorProfile $author,
        public Collection $posts,
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $count = $this->posts->count();
        $authorName = $this->author->display_name ?? $this->author->slug;

        return new Envelope(
            subject: '📚 '.$count.' nouveau'.($count > 1 ? 'x' : '').' article'.($count > 1 ? 's' : '').' de '.$authorName,
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address((string) ($this->author->email ?? $fromAddress))],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.$this->unsubscribeOneClickUrl().'>, <'.$this->unsubscribeUrl().'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'authors::mail.subscriber-digest',
            with: [
                'subscriber' => $this->subscriber,
                'author' => $this->author,
                'posts' => $this->posts,
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
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

    public function unsubscribeOneClickUrl(): string
    {
        return URL::signedRoute(
            'authors.newsletter.unsubscribe-1click',
            [
                'slug' => $this->author->slug,
                'token' => $this->subscriber->confirmation_token,
            ]
        );
    }
}
