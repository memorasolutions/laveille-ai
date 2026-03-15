<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
