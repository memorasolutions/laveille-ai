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
use Modules\Authors\Models\AuthorComment;

class CommentReplyNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public AuthorComment $comment, public AuthorComment $parent)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💬 '.$this->comment->author_name.' a répondu à votre commentaire sur la veille de Stef',
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            replyTo: [new Address((string) config('mail.from.address'))],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.url('/notification-preferences').'>',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'authors::mail.comment-reply-notification',
            with: ['commentUrl' => $this->commentUrl()],
        );
    }

    public function commentUrl(): string
    {
        return url('/').'#comment-'.$this->comment->id;
    }
}
