<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class DigestMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public Collection $notifications) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos notifications - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications::email.digest',
        );
    }
}
