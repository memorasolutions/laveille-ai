<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services\Tax;

use Modules\Ecommerce\Contracts\TaxCalculatorInterface;

class CanadaTaxCalculator implements TaxCalculatorInterface
{
    /** @var array<string, array<int, array{name: string, rate: float}>> */
    private const array RATES = [
        // GST only (5%)
        'AB' => [['name' => 'GST', 'rate' => 0.05]],
        'NT' => [['name' => 'GST', 'rate' => 0.05]],
        'NU' => [['name' => 'GST', 'rate' => 0.05]],
        'YT' => [['name' => 'GST', 'rate' => 0.05]],

        // HST (13%)
        'ON' => [['name' => 'HST', 'rate' => 0.13]],

        // HST (15%)
        'NB' => [['name' => 'HST', 'rate' => 0.15]],
        'NL' => [['name' => 'HST', 'rate' => 0.15]],
        'NS' => [['name' => 'HST', 'rate' => 0.15]],
        'PE' => [['name' => 'HST', 'rate' => 0.15]],

        // GST + QST
        'QC' => [
            ['name' => 'GST', 'rate' => 0.05],
            ['name' => 'QST', 'rate' => 0.09975],
        ],

        // GST + PST
        'SK' => [
            ['name' => 'GST', 'rate' => 0.05],
            ['name' => 'PST', 'rate' => 0.06],
        ],
        'MB' => [
            ['name' => 'GST', 'rate' => 0.05],
            ['name' => 'PST', 'rate' => 0.07],
        ],
        'BC' => [
            ['name' => 'GST', 'rate' => 0.05],
            ['name' => 'PST', 'rate' => 0.07],
        ],
    ];

    public function calculateTax(float $subtotal, string $province): TaxResult
    {
        $code = strtoupper($province);
        $rates = self::RATES[$code] ?? self::RATES['QC'];

        $breakdown = [];
        $totalTax = 0.0;

        foreach ($rates as $tax) {
            $amount = round($subtotal * $tax['rate'], 2);
            $breakdown[] = [
                'name' => $tax['name'],
                'rate' => $tax['rate'],
                'amount' => $amount,
            ];
            $totalTax += $amount;
        }

        return new TaxResult(round($totalTax, 2), $breakdown);
    }

    /** @return array<int, array{name: string, rate: float, amount: float}> */
    public function getTaxBreakdown(float $subtotal, string $province): array
    {
        return $this->calculateTax($subtotal, $province)->getBreakdown();
    }
}
