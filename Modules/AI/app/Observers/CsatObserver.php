<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Observers;

use Modules\AI\Models\CsatSurvey;
use Modules\AI\Models\Ticket;

class CsatObserver
{
    public function updated(Ticket $ticket): void
    {
        $newStatus = $ticket->status->value ?? $ticket->status;
        $oldStatus = $ticket->getOriginal('status');

        if (in_array($newStatus, ['resolved', 'closed']) && $oldStatus !== $newStatus) {
            // Check if CSAT survey already exists
            if (! CsatSurvey::where('ticket_id', $ticket->id)->exists()) {
                // Frontend widget will prompt user for CSAT feedback
                // No server-side action needed here
            }
        }
    }
}
