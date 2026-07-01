<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * SRS - Relances de révision espacée planifiées. Pour chaque apprenant ayant au
 * moins une carte due, envoie UNE relance quotidienne via AcademyNotificationService
 * (deeplink vers la session de révision). IDEMPOTENT : le dédoublonnage journalier
 * (academy_notification_logs, clé « srs:AAAAMMJJ:user ») garantit au plus une
 * relance par jour et par utilisateur.
 *
 * DOUBLE GARDE de sortie précoce (zéro requête superflue) :
 *   1. drapeau academy.srs_enabled (défaut FALSE) -> commande no-op ;
 *   2. interrupteur maître des notifications (défaut FALSE) -> aucun envoi.
 */

declare(strict_types=1);

namespace Modules\Academy\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Academy\Models\SrsCard;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Academy\Services\SrsService;
use Throwable;

class SrsRemindCommand extends Command
{
    protected $signature = 'academy:srs-remind';

    protected $description = 'Relance les apprenants ayant des cartes SRS dues (idempotent, gardé par le drapeau SRS et l\'interrupteur maître)';

    public function handle(
        SrsService $srs,
        AcademyNotificationService $notifications,
    ): int {
        // GARDE 1 - drapeau SRS (défaut FALSE). No-op complet.
        if (! $srs->isEnabled()) {
            $this->info('Répétition espacée désactivée (academy.srs_enabled off) : aucune relance.');

            return self::SUCCESS;
        }

        // GARDE 2 - interrupteur maître des notifications (défaut FALSE).
        if (! $notifications->isMasterEnabled()) {
            $this->info('Notifications de l\'Académie désactivées (interrupteur maître off) : aucune relance envoyée.');

            return self::SUCCESS;
        }

        // Utilisateurs distincts ayant au moins une carte due (due_at passée ou nulle).
        $userIds = SrsCard::query()
            ->where(function ($q): void {
                $q->whereNull('due_at')->orWhere('due_at', '<=', now());
            })
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('Aucune carte due : rien à relancer.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach (User::whereIn('id', $userIds)->cursor() as $user) {
            try {
                $dueCount = $srs->dueCountFor($user);

                if ($dueCount > 0 && $notifications->srsReminder($user, $dueCount)) {
                    $sent++;
                }
            } catch (Throwable $e) {
                // Best-effort : ne jamais faire échouer la commande pour un utilisateur.
                $this->warn("Relance SRS ignorée pour l'utilisateur {$user->id} : {$e->getMessage()}");
            }
        }

        $this->info("Relances SRS envoyées : {$sent}.");

        return self::SUCCESS;
    }
}
