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
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorWebmention;

class WebmentionReceivedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuthorWebmention $webmention,
        public AuthorPost $post,
        public AuthorProfile $author,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔗 Nouvelle mention web sur "'.$this->post->title.'"',
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            replyTo: [new Address((string) config('mail.from.address'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'authors::mail.webmention-received',
        );
    }
}
