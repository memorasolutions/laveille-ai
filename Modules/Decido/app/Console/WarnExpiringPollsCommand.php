<?php

declare(strict_types=1);

namespace Modules\Decido\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Decido\Mail\PollExpiringSoonMail;
use Modules\Decido\Models\Poll;

/**
 * Avertissement courriel UNIQUE (pas de cascade J-30/J-7/J-15 - décision tranchée, non intrusif)
 * envoyé aux créateurs de sondages Decido dont l'expiration approche (fenêtre configurée par
 * decido.expiry_warning_days_before, 14 jours par défaut).
 *
 * Planifiée à 06h00, AVANT decido:purge-expired (06h15) - routes/console.php - pour qu'un
 * sondage sur le point d'expirer reçoive toujours son avertissement avant d'être potentiellement
 * purgé le même jour.
 *
 * Idempotence : expiry_warned_at (nullable) est marqué juste après l'envoi et exclut le sondage
 * des exécutions suivantes tant que l'échéance n'a pas changé (une prolongation - voir
 * PollManageController::extend() - remet expiry_warned_at à NULL pour permettre un futur
 * avertissement sur la NOUVELLE échéance).
 *
 * creator_id NULL (compte supprimé depuis, cf. orphan_instead_of_cascade_decido_polls_creator) :
 * ignoré silencieusement par le whereNotNull('creator_id') ci-dessous - rien à qui écrire, mais
 * le sondage continue son cycle de purge normal (decido:purge-expired ne dépend jamais de
 * expiry_warned_at).
 */
class WarnExpiringPollsCommand extends Command
{
    protected $signature = 'decido:warn-expiring-polls';

    protected $description = "Envoie un avertissement courriel unique (J-14) avant la suppression automatique d'un sondage Decido";

    public function handle(): int
    {
        $warningDays = (int) config('decido.expiry_warning_days_before', 14);

        $polls = Poll::whereNotNull('expires_at')
            ->whereNull('expiry_warned_at')
            ->whereNotNull('creator_id')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays($warningDays))
            ->with('creator')
            ->get();

        $sent = 0;

        foreach ($polls as $poll) {
            // Garde-fou supplémentaire malgré whereNotNull('creator_id') ci-dessus : la relation
            // ->creator peut rester null si l'utilisateur a été supprimé entre la requête et
            // l'itération (fenêtre de course improbable mais non nulle sur un cron quotidien).
            if (! $poll->creator || ! $poll->creator->email) {
                continue;
            }

            try {
                Mail::to($poll->creator->email)->send(new PollExpiringSoonMail($poll));
                $poll->update(['expiry_warned_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                // Un échec d'envoi isolé (SMTP temporairement indisponible, adresse invalide) ne
                // doit pas bloquer l'avertissement des autres sondages de ce lot - expiry_warned_at
                // n'est PAS marqué ici, le sondage sera retenté à la prochaine exécution
                // quotidienne tant qu'il reste dans la fenêtre J-14.
                Log::warning("decido:warn-expiring-polls - echec d'envoi pour le sondage #{$poll->id}: {$e->getMessage()}");
            }
        }

        $this->info("Avertissements Decido envoyes : {$sent} / {$polls->count()} sondage(s) dans la fenetre J-{$warningDays}.");

        return self::SUCCESS;
    }
}
