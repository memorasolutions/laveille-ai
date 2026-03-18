<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\AbandonedCartReminder;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Notifications\AbandonedCartNotification;

class ProcessAbandonedCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('ecommerce');
    }

    public function handle(): void
    {
        /** @var array<int, int> */
        $schedule = config('modules.ecommerce.abandoned_cart.schedule', [1 => 1, 24 => 2, 72 => 3]);

        $carts = Cart::query()
            ->where('updated_at', '<', now()->subHour())
            ->whereNotNull('user_id')
            ->whereHas('items')
            ->with(['user', 'items.variant.product', 'reminders'])
            ->get();

        foreach ($carts as $cart) {
            // Skip if user already placed an order after cart was last updated
            if (Order::where('user_id', $cart->user_id)->where('created_at', '>', $cart->updated_at)->exists()) {
                continue;
            }

            $hoursSince = (int) $cart->updated_at->diffInHours(now());
            $sentNumbers = $cart->reminders->pluck('reminder_number')->toArray();

            foreach ($schedule as $hoursThreshold => $reminderNumber) {
                if ($hoursSince >= $hoursThreshold && ! in_array($reminderNumber, $sentNumbers, true)) {
                    // Sequential: don't send #2 if #1 wasn't sent
                    if ($reminderNumber > 1 && ! in_array($reminderNumber - 1, $sentNumbers, true)) {
                        continue;
                    }

                    // Notification handled by SendAbandonedCartReminder listener
                    \Modules\Ecommerce\Events\CartAbandoned::dispatch($cart, $reminderNumber);

                    AbandonedCartReminder::create([
                        'cart_id' => $cart->id,
                        'user_id' => $cart->user_id,
                        'reminder_number' => $reminderNumber,
                        'sent_at' => now(),
                    ]);

                    break; // One reminder per run per cart
                }
            }
        }
    }
}
