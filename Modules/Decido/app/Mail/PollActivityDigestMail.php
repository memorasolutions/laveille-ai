<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Decido\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Mail\Traits\RoutesToWorkspaceMailer;
use Modules\Decido\Models\Poll;

/**
 * LOT 5 (docs/specs/2026-08-16-decido-reste-a-faire.md) : résumé quotidien GROUPÉ envoyé au
 * créateur d'un sondage Decido quand de l'activité nouvelle (vote, déclin, commentaire) est
 * survenue depuis le dernier résumé. Message transactionnel vers le propre compte du créateur
 * (adresse déjà vérifiée, creator_id non nul) - aucune collecte nouvelle, jamais envoyé à un
 * votant.
 *
 * REGROUPEMENT (le piège à traiter) : un résumé quotidien au maximum par sondage, jamais un
 * courriel par vote individuel - voir NotifyPollActivityCommand::handle() pour le calcul de
 * l'activité nouvelle et son idempotence (activity_notified_at, réécrit à chaque envoi).
 */
class PollActivityDigestMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(
        public Poll $poll,
        public int $newVoters,
        public int $newDeclines,
        public int $newComments,
    ) {}

    public function build(): static
    {
        $this->routeToWorkspaceMailer();

        // Même limite structurelle que PollExpiringSoonMail : le jeton admin réel n'est jamais
        // persisté nulle part après sa création (seul son hash SHA-256 l'est) - le jeton
        // placeholder 'proprietaire' fonctionne parce que PollManageController::authorizeManage()
        // accorde l'accès via le bypass propriétaire (Auth::id() === poll->creator_id) dès que le
        // destinataire est connecté, indépendamment de la valeur du jeton.
        $manageUrl = route('decido.manage', ['poll' => $this->poll->public_id, 'adminToken' => 'proprietaire']);

        return $this->subject('Du nouveau sur ton sondage « '.$this->poll->title.' »')
            ->markdown('decido::emails.activity-digest')
            ->with([
                'poll_title' => $this->poll->title,
                'creator_name' => $this->poll->creator?->name,
                'new_voters' => $this->newVoters,
                'new_declines' => $this->newDeclines,
                'new_comments' => $this->newComments,
                'manage_url' => $manageUrl,
                'brand_name' => config('app.name'),
            ]);
    }
}
