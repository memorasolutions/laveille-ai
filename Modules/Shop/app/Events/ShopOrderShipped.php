<?php

namespace Modules\Shop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Shop\Models\Order;

class ShopOrderShipped
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order, public ?string $trackingNumber = null, public ?string $trackingUrl = null) {}
}
