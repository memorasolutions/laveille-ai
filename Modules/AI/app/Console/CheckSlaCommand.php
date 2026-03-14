<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\AI\Models\Ticket;
use Modules\AI\Notifications\TicketSlaWarningNotification;

class CheckSlaCommand extends Command
{
    protected $signature = 'ai:check-sla';

    protected $description = 'Vérifie les tickets proches de l\'échéance SLA et envoie des alertes';

    public function handle(): void
    {
        $tickets = Ticket::whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addHours(2))
            ->where('due_at', '>', now())
            ->get();

        $count = $tickets->count();
        $managers = User::permission('manage_ai')->get();

        foreach ($tickets as $ticket) {
            $notified = collect();

            if ($ticket->agent_id) {
                $agent = User::find($ticket->agent_id);
                if ($agent) {
                    $agent->notify(new TicketSlaWarningNotification($ticket));
                    $notified->push($agent->id);
                }
            }

            foreach ($managers as $manager) {
                if (! $notified->contains($manager->id)) {
                    $manager->notify(new TicketSlaWarningNotification($ticket));
                }
            }
        }

        $this->info("Checked {$count} tickets approaching SLA deadline.");
    }
}
