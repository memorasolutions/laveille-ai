<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Listeners;

use App\Models\User;
use Modules\Ecommerce\Events\LowStockDetected;
use Modules\Ecommerce\Notifications\LowStockNotification;

class NotifyAdminsLowStock
{
    public function handle(LowStockDetected $event): void
    {
        $admins = User::role('super_admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new LowStockNotification($event->variant));
        }
    }
}
