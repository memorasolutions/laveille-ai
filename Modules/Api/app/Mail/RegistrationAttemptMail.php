<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Api\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Courriel transactionnel envoyé au propriétaire d'un compte EXISTANT lorsqu'une
 * tentative d'inscription est faite avec son adresse via POST /api/v1/register.
 *
 * SÉCURITÉ #254 (v1.19.15) : la réponse HTTP de /register est identique que
 * l'email existe ou non (anti user enumeration). Ce mail avertit en privé le
 * propriétaire légitime pour qu'il puisse réagir si la tentative n'est pas de lui.
 *
 * Loi 25 (QC) : transactionnel légitime (sécurité du compte) — pas de consentement
 * marketing requis. Pas de tracking pixel, pas de lien d'unsubscribe (mail de sécurité).
 */
class RegistrationAttemptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tentative d\'inscription sur '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'api::mail.registration-attempt',
            with: [
                'userName' => $this->user->name,
                'loginUrl' => url('/login'),
                'appName' => config('app.name'),
            ],
        );
    }
}
