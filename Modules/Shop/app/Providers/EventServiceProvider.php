<?php

namespace Modules\Shop\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Events\ShopOrderShipped;
use Modules\Shop\Listeners\SendOrderConfirmation;
use Modules\Shop\Listeners\SendOrderShippedNotification;
use Modules\Shop\Listeners\CreateGelatoOrder;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ShopOrderPaid::class => [
            SendOrderConfirmation::class,
            CreateGelatoOrder::class,
        ],
        ShopOrderShipped::class => [
            SendOrderShippedNotification::class,
        ],
    ];
}
