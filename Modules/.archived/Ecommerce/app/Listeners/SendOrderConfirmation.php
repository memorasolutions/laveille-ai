<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Ecommerce\Events\OrderPaid;
use Modules\Ecommerce\Notifications\OrderConfirmationNotification;

class SendOrderConfirmation implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        /** @var \App\Models\User|null $user */
        $user = $event->order->user;
        $user?->notify(new OrderConfirmationNotification($event->order));
    }
}
