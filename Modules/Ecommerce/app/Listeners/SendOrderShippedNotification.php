<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Ecommerce\Events\OrderShipped;
use Modules\Ecommerce\Notifications\OrderShippedNotification;

class SendOrderShippedNotification implements ShouldQueue
{
    public function handle(OrderShipped $event): void
    {
        /** @var \App\Models\User|null $user */
        $user = $event->order->user;
        $user?->notify(new OrderShippedNotification($event->order));
    }
}
