<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Blog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Blog\Models\Article;

class ArticleSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Article $article,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->status === 'approved'
                ? __('Votre article a été publié !')
                : __('Mise à jour sur votre soumission'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'blog::emails.submission-notification',
            with: [
                'article' => $this->article,
                'status' => $this->status,
            ],
        );
    }
}
