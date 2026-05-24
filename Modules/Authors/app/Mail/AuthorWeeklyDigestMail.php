<?php

declare(strict_types=1);

namespace Modules\Authors\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Authors\Models\AuthorProfile;

class AuthorWeeklyDigestMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuthorProfile $author,
        public array $stats,
        public Carbon $weekStart,
        public Carbon $weekEnd
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📊 Ton bilan hebdo · '.$this->weekStart->translatedFormat('d M').'–'.$this->weekEnd->translatedFormat('d M Y'),
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'authors::mail.weekly-digest');
    }
}
