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
 * Courriel de rappel OPT-IN pour un visiteur anonyme, demandé à la création (section 6.7,
 * décision du fondateur 2026-09-25). Envoyé au plus une fois (reminder_sent_at), à l'approche de
 * la purge effective (cet envoi conditionne ensuite la purge elle-même, M1.3). Ne contient JAMAIS
 * le lien secret d'administration - seulement une invitation générique à revenir sur laveille.ai
 * retrouver sa signature PAR SES PROPRES MOYENS.
 */
class SignatureReminderMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(public Signature $signature) {}

    public function build(): static
    {
        $this->routeToWorkspaceMailer();

        return $this->subject('Rappel : ta signature de courriel sur laveille.ai')
            ->markdown('signature::emails.reminder')
            ->with([
                'assistant_url' => route('signature.assistant'),
                'brand_name' => config('app.name'),
            ]);
    }
}
