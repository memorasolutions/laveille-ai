<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\AI\Events\HumanTakeoverRequested;
use Modules\AI\Notifications\HumanTakeoverNotification;

class NotifyAgentsOfTakeover implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(HumanTakeoverRequested $event): void
    {
        $agents = User::permission('manage_ai')->get();

        foreach ($agents as $agent) {
            $agent->notify(new HumanTakeoverNotification($event->conversation));
        }
    }
}
