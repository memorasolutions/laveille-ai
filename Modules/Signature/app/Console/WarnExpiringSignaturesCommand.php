<?php

declare(strict_types=1);

namespace Modules\Signature\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Signature\Mail\SignatureExpiringSoonMail;
use Modules\Signature\Mail\SignatureReminderMail;
use Modules\Signature\Models\Signature;

/**
 * Avertissement UNIQUE (5 mois d'inactivité, avant la purge à 6 mois - config
 * signature.warning_months / signature.retention_months) - couvre DEUX publics distincts dans la
 * même commande quotidienne (plan, section 11.4) :
 *   - membres : courriel de rappel (SignatureExpiringSoonMail), idempotent via expiry_warned_at.
 *     Depuis M1.4, une signature de membre n'est JAMAIS purgée automatiquement tant que le compte
 *     existe - ce courriel reste malgré tout utile comme simple RAPPEL D'INACTIVITÉ (jamais une
 *     menace de suppression, voir le contenu du gabarit) ;
 *   - visiteurs anonymes ayant fourni un courriel de rappel opt-in à la création (section 6.7) :
 *     SignatureReminderMail, idempotent via reminder_sent_at, JAMAIS le lien secret dedans. Pour
 *     ce public, l'envoi conditionne ensuite la purge elle-même (M1.3).
 *
 * « À l'approche de l'inactivité prolongée » (section 6.7) : le seuil d'avertissement utilise la
 * MÊME règle à deux signaux que la purge (Signature::isInactivePubliclyFor()), simplement évaluée
 * à warning_months au lieu de retention_months - un chargement d'image récent retarde donc AUSSI
 * l'avertissement, pas seulement la purge. Contrairement à Signature::isEligibleForPurge(), cette
 * méthode ne porte PAS l'exclusion des membres (M1.4) : un membre reste averti même si sa
 * signature ne sera jamais purgée automatiquement.
 *
 * Planifiée AVANT signature:purge-expired (routes/console.php), même ordre que Décido.
 */
class WarnExpiringSignaturesCommand extends Command
{
    protected $signature = 'signature:warn-expiring';

    protected $description = "Avertissement unique (5 mois d'inactivité) avant la suppression automatique d'une signature de courriel";

    public function handle(): int
    {
        $warningMonths = (int) config('signature.warning_months', 5);

        $memberWarned = $this->warnMembers($warningMonths);
        $reminderSent = $this->sendAnonymousReminders($warningMonths);

        $this->info("Avertissements signature envoyés : {$memberWarned} membre(s), {$reminderSent} rappel(s) opt-in.");

        return self::SUCCESS;
    }

    private function warnMembers(int $warningMonths): int
    {
        $sent = 0;

        Signature::whereNotNull('user_id')
            ->where('status', '!=', Signature::STATUS_PURGED)
            ->whereNull('expiry_warned_at')
            ->with('user')
            ->chunkById(500, function ($signatures) use (&$sent, $warningMonths) {
                foreach ($signatures as $sig) {
                    if (! $sig->isInactivePubliclyFor($warningMonths)) {
                        continue;
                    }
                    if (! $sig->user || ! $sig->user->email) {
                        continue;
                    }

                    try {
                        Mail::to($sig->user->email)->send(new SignatureExpiringSoonMail($sig));
                        $sig->update(['expiry_warned_at' => now()]);
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning("signature:warn-expiring - echec d'envoi (membre) pour la signature #{$sig->id}: {$e->getMessage()}");
                    }
                }
            });

        return $sent;
    }

    private function sendAnonymousReminders(int $warningMonths): int
    {
        $sent = 0;

        Signature::whereNotNull('reminder_email')
            ->where('status', '!=', Signature::STATUS_PURGED)
            ->whereNull('reminder_sent_at')
            ->chunkById(500, function ($signatures) use (&$sent, $warningMonths) {
                foreach ($signatures as $sig) {
                    if (! $sig->isInactivePubliclyFor($warningMonths)) {
                        continue;
                    }

                    try {
                        Mail::to($sig->reminder_email)->send(new SignatureReminderMail($sig));
                        $sig->update(['reminder_sent_at' => now()]);
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning("signature:warn-expiring - echec d'envoi (rappel opt-in) pour la signature #{$sig->id}: {$e->getMessage()}");
                    }
                }
            });

        return $sent;
    }
}
