<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Contracts;

use Modules\Ecommerce\Services\Tax\TaxResult;

interface TaxCalculatorInterface
{
    public function calculateTax(float $subtotal, string $province): TaxResult;

    /** @return array<int, array{name: string, rate: float, amount: float}> */
    public function getTaxBreakdown(float $subtotal, string $province): array;
}
