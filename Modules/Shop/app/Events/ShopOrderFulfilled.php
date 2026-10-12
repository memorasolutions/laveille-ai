<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Shop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Shop\Models\Order;

class ShopOrderFulfilled
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
