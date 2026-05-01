<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Cart;

class CartAbandoned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Cart $cart,
        public int $reminderNumber,
    ) {}
}
