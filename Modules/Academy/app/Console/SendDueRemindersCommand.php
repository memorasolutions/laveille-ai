<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - Rappels d'échéance planifiés (parité Moodle). Pour chaque utilisateur
 * inscrit actif, envoie un rappel des échéances à venir dans la fenêtre
 * configurée (défaut J-2). IDEMPOTENT : le journal anti-doublon
 * (academy_notification_logs) empêche tout second rappel de la même échéance.
 *
 * SÉCURITÉ : la commande SORT IMMÉDIATEMENT si l'interrupteur maître
 * (academy.notifications.enabled, défaut FALSE) est désactivé : zéro envoi,
 * zéro requête superflue. La préférence « due_reminder » de chaque utilisateur
 * est en plus respectée par AcademyNotificationService.
 */

declare(strict_types=1);

namespace Modules\Academy\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Academy\Services\CalendarService;
use Throwable;

class SendDueRemindersCommand extends Command
{
    protected $signature = 'academy:send-due-reminders';

    protected $description = 'Envoie les rappels d\'échéance à venir aux inscrits actifs (idempotent, gardé par l\'interrupteur maître)';

    public function handle(
        AcademyNotificationService $notifications,
        CalendarService $calendar,
    ): int {
        // INTERRUPTEUR MAITRE : aucun envoi si désactivé (défaut). Sortie précoce.
        if (! $notifications->isMasterEnabled()) {
            $this->info('Notifications de l\'Académie désactivées (interrupteur maître off) : aucun rappel envoyé.');

            return self::SUCCESS;
        }

        $windowDays = (int) config('academy.notifications.reminder_window_days', 2);
        $windowDays = max(1, $windowDays);
        $limit      = now()->addDays($windowDays);

        // Utilisateurs ayant au moins une inscription active (scope strict).
        $userIds = Enrollment::query()
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            $this->info('Aucun inscrit actif : rien à rappeler.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach (User::whereIn('id', $userIds)->cursor() as $user) {
            try {
                // Échéances FUTURES de l'utilisateur (déjà scopées à ses inscriptions actives).
                $events = $calendar->upcomingForUser($user, 50);

                foreach ($events as $event) {
                    $startsAt = $event['starts_at'] ?? null;

                    // Ne rappeler QUE les échéances tombant dans la fenêtre (à venir, <= J + fenêtre).
                    if (! is_object($startsAt) || ! method_exists($startsAt, 'lessThanOrEqualTo')) {
                        continue;
                    }
                    // Ignorer les échéances déjà passées (aucun rappel rétroactif).
                    if ($startsAt->lessThan(now())) {
                        continue;
                    }
                    if ($startsAt->greaterThan($limit)) {
                        continue;
                    }

                    if ($notifications->dueReminder($user, $event)) {
                        $sent++;
                    }
                }
            } catch (Throwable $e) {
                // Ne jamais faire échouer la commande pour un utilisateur : best-effort.
                $this->warn("Rappel ignoré pour l'utilisateur {$user->id} : {$e->getMessage()}");
            }
        }

        $this->info("Rappels d'échéance envoyés : {$sent}.");

        return self::SUCCESS;
    }
}
