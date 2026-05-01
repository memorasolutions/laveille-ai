<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Contracts\TaxCalculatorInterface;
use Modules\Ecommerce\Services\Tax\TaxResult;

class TaxService
{
    public function __construct(
        private ?TaxCalculatorInterface $calculator = null,
    ) {}

    /**
     * Calculate tax for a subtotal. If a province is provided and a calculator
     * is bound, uses multi-jurisdiction logic. Otherwise falls back to flat rate.
     */
    public function calculateTax(float $subtotal, ?string $province = null): float
    {
        if ($province && $this->calculator) {
            return $this->calculator->calculateTax($subtotal, $province)->getTotal();
        }

        return round($subtotal * $this->getTaxRate() / 100, 2);
    }

    /**
     * Get detailed tax breakdown by jurisdiction.
     *
     * @return array<int, array{name: string, rate: float, amount: float}>
     */
    public function getTaxBreakdown(float $subtotal, string $province): array
    {
        if ($this->calculator) {
            return $this->calculator->getTaxBreakdown($subtotal, $province);
        }

        return [['name' => 'Tax', 'rate' => $this->getTaxRate() / 100, 'amount' => $this->calculateTax($subtotal)]];
    }

    /**
     * Get the detailed TaxResult object.
     */
    public function calculateTaxResult(float $subtotal, string $province): TaxResult
    {
        if ($this->calculator) {
            return $this->calculator->calculateTax($subtotal, $province);
        }

        $amount = $this->calculateTax($subtotal);

        return new TaxResult($amount, [['name' => 'Tax', 'rate' => $this->getTaxRate() / 100, 'amount' => $amount]]);
    }

    public function getTaxRate(): float
    {
        return (float) config('modules.ecommerce.tax_rate', 0);
    }
}
