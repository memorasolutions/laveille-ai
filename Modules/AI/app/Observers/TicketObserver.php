<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Observers;

use App\Models\User;
use Modules\AI\Models\Ticket;
use Modules\AI\Notifications\TicketAssignedNotification;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        if ($ticket->isDirty('agent_id') && $ticket->agent_id !== null) {
            $agent = User::find($ticket->agent_id);
            if ($agent) {
                $agent->notify(new TicketAssignedNotification($ticket));
            }
        }
    }
}
