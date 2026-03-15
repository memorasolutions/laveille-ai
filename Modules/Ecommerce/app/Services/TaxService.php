<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

class TaxService
{
    public function calculateTax(float $subtotal): float
    {
        return round($subtotal * $this->getTaxRate() / 100, 2);
    }

    public function getTaxRate(): float
    {
        return (float) config('modules.ecommerce.tax_rate', 0);
    }
}
