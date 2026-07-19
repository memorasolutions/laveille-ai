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
 * Avertissement UNIQUE (pas de cascade J-30/J-7 - décision tranchée, non intrusif) envoyé à J-14
 * avant la suppression automatique d'un sondage Decido, cf. WarnExpiringPollsCommand.
 *
 * Idempotence : la commande qui déclenche cet envoi marque expiry_warned_at APRÈS le send() -
 * ce Mailable lui-même ne connaît rien de l'idempotence, qui reste la responsabilité du
 * planificateur (séparation des responsabilités, cf. WarnExpiringPollsCommand::handle()).
 */
class PollExpiringSoonMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(public Poll $poll) {}

    public function build(): static
    {
        $this->routeToWorkspaceMailer();

        // Jeton placeholder ('proprietaire') - même pattern que manage/index.blade.php (lien
        // "Gérer" de la liste "Mes sondages") : PollManageController::authorizeManage() accorde
        // l'accès via le bypass propriétaire (Auth::id() === poll->creator_id) dès que le
        // destinataire est connecté, indépendamment de la valeur du jeton. Le jeton ADMIN réel
        // n'est jamais persisté nulle part après sa création (seul son hash SHA-256 l'est) - il
        // est donc structurellement impossible de le reconstituer ici pour bâtir un vrai lien
        // magique. Un clic sans être connecté renvoie 403 - même limite déjà présente pour le
        // lien "Gérer" de "Mes sondages", pas une régression introduite par ce Mailable.
        $manageUrl = route('decido.manage', ['poll' => $this->poll->public_id, 'adminToken' => 'proprietaire']);

        return $this->subject('Ton sondage « '.$this->poll->title.' » sera bientôt supprimé')
            ->markdown('decido::emails.expiring-soon')
            ->with([
                'poll_title' => $this->poll->title,
                'creator_name' => $this->poll->creator?->name,
                'deletion_date' => $this->poll->expires_at?->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'manage_url' => $manageUrl,
                'brand_name' => config('app.name'),
            ]);
    }
}
