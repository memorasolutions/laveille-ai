<?php

namespace Modules\Shop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Shop\Models\Order;

class ShopOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
