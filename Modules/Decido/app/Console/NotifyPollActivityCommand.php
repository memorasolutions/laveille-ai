<?php

declare(strict_types=1);

namespace Modules\Decido\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Decido\Mail\PollActivityDigestMail;
use Modules\Decido\Models\Poll;

/**
 * LOT 5 (docs/specs/2026-08-16-decido-reste-a-faire.md) : résumé quotidien GROUPÉ de l'activité
 * (votes, déclins, commentaires) envoyé aux créateurs de sondages Decido, réutilisant EXACTEMENT
 * le même mécanisme d'envoi que WarnExpiringPollsCommand (mailer 'workspace' via
 * RoutesToWorkspaceMailer, garde d'idempotence par horodatage, échec d'un envoi isolé qui
 * n'interrompt jamais le lot).
 *
 * LE PIÈGE À TRAITER (voir la spec) : dix participants qui votent une fois donnent dix
 * courriels ; quelqu'un qui modifie sa réponse trois fois en donne treize. Sans regroupement,
 * une bonne idée devient une nuisance et finit désactivée.
 *
 * DÉCISION RETENUE : un résumé QUOTIDIEN par sondage, jamais plus d'un par jour, envoyé
 * SEULEMENT s'il y a du nouveau depuis le dernier envoi (Poll::newActivitySince()). Écarté :
 * - « notifier au premier vote puis silence » : un sondage vivant reçoit des votes en rafales
 *   étalées sur plusieurs jours (partage du lien à des moments différents) - une notification
 *   UNIQUE totale laisserait l'organisateur sans nouvelle après le premier votant, exactement le
 *   problème que ce lot doit résoudre. Le cas d'usage réel de cette semaine (webinaire, premier
 *   invité) est justement celui où l'activité s'étale sur plusieurs jours.
 * - « seuil déclaré par l'organisateur » : exige une configuration AVANT que la valeur du
 *   résumé soit visible - un sondage à 3 votes qui n'atteint jamais un seuil de 5 ne
 *   préviendrait JAMAIS son créateur, pire que l'absence actuelle de notification.
 * Le résumé quotidien est borné (au plus 1 courriel/jour/sondage, quel que soit le nombre de
 * votes reçus ce jour-là - le critère "l'organisateur ne doit jamais avoir envie de le
 * désactiver" est structurellement garanti par ce plafond) et ne nécessite AUCUNE configuration
 * préalable (silencieux tant qu'il n'y a rien de neuf, jamais un résumé vide).
 *
 * Planifiée à 07h00 (routes/console.php), après decido:warn-expiring-polls (06h00) et
 * decido:purge-expired (06h15) - aucune dépendance entre les trois, ordre choisi seulement pour
 * garder tous les crons Decido groupés au même moment de la nuit.
 *
 * Idempotence : activity_notified_at (nullable) sert de curseur "depuis quand chercher du
 * nouveau" - contrairement à expiry_warned_at (WarnExpiringPollsCommand), il est réécrit à
 * CHAQUE envoi (jamais une seule fois pour la vie du sondage), puisque ce résumé est censé se
 * répéter tant qu'il y a du nouveau. Un sondage sans aucune activité nouvelle depuis le dernier
 * passage ne génère aucun courriel (comptage à zéro sur les trois axes) : le silence est le
 * comportement normal, pas une erreur.
 *
 * creator_id NULL (compte supprimé) : ignoré silencieusement par le whereNotNull('creator_id')
 * ci-dessous, même garde que WarnExpiringPollsCommand - rien à qui écrire.
 */
class NotifyPollActivityCommand extends Command
{
    protected $signature = 'decido:notify-poll-activity';

    protected $description = "Envoie un résumé quotidien groupé de l'activité (votes/déclins/commentaires) aux créateurs de sondages Decido, si du nouveau est survenu depuis le dernier résumé";

    public function handle(): int
    {
        // draft = pas encore partagé, donc structurellement sans aucune activité possible -
        // inutile de l'inclure dans la requête.
        $polls = Poll::where('status', '!=', 'draft')
            ->where('activity_notifications_enabled', true)
            ->whereNotNull('creator_id')
            ->with('creator')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($polls as $poll) {
            // Garde-fou supplémentaire malgré whereNotNull('creator_id') ci-dessus : même fenêtre
            // de course improbable que WarnExpiringPollsCommand (utilisateur supprimé entre la
            // requête et l'itération).
            if (! $poll->creator || ! $poll->creator->email) {
                continue;
            }

            $since = $poll->activity_notified_at ?? $poll->created_at;
            $activity = $poll->newActivitySince($since);

            if ($activity['voters'] === 0 && $activity['declines'] === 0 && $activity['comments'] === 0) {
                // Rien de nouveau depuis le dernier résumé (ou depuis la création s'il n'y en a
                // jamais eu) : SILENCE, c'est le comportement voulu, pas une erreur. C'est ce
                // garde-fou qui empêche le résumé quotidien de dégénérer en courriel-poubelle
                // envoyé même sans nouveauté.
                $skipped++;

                continue;
            }

            try {
                Mail::to($poll->creator->email)->send(new PollActivityDigestMail(
                    $poll,
                    $activity['voters'],
                    $activity['declines'],
                    $activity['comments'],
                ));
                $poll->update(['activity_notified_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                // Même politique que WarnExpiringPollsCommand : un échec d'envoi isolé (SMTP
                // temporairement indisponible, adresse invalide) ne doit pas bloquer les autres
                // sondages de ce lot - activity_notified_at n'est PAS avancé ici, l'activité sera
                // retentée (et cumulée, pas perdue) au prochain passage quotidien.
                Log::warning("decido:notify-poll-activity - echec d'envoi pour le sondage #{$poll->id}: {$e->getMessage()}");
            }
        }

        $this->info("Résumés d'activité Decido envoyés : {$sent} sondage(s) avec nouveauté, {$skipped} sans changement depuis le dernier résumé.");

        return self::SUCCESS;
    }
}
