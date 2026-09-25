<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Signature\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Mail\Traits\RoutesToWorkspaceMailer;
use Modules\Signature\Models\Signature;

/**
 * Rappel d'inactivité UNIQUE (5 mois sans y toucher) envoyé au MEMBRE propriétaire d'une
 * signature - jamais au visiteur anonyme, qui reçoit plutôt SignatureReminderMail (section 6.7,
 * courriel de rappel opt-in). Patron exact de Modules\Decido\Mail\PollExpiringSoonMail pour le
 * mécanisme d'envoi, MAIS PAS pour le contenu : depuis M1.4, une signature de membre n'est JAMAIS
 * purgée automatiquement tant que le compte existe, donc ce courriel ne menace plus d'une
 * suppression qui n'aura jamais lieu - c'est un simple rappel, jamais un compte à rebours.
 *
 * Idempotence : la commande qui déclenche cet envoi marque expiry_warned_at APRÈS le send()
 * (Modules\Signature\Console\WarnExpiringSignaturesCommand::handle()).
 */
class SignatureExpiringSoonMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(public Signature $signature) {}

    public function build(): static
    {
        $this->routeToWorkspaceMailer();

        return $this->subject('Ta signature de courriel - un petit rappel')
            ->markdown('signature::emails.expiring-soon')
            ->with([
                'owner_name' => $this->signature->user?->name,
                'manage_url' => route('signature.user.index'),
                'brand_name' => config('app.name'),
            ]);
    }
}
