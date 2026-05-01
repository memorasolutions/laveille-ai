<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services\Tax;

readonly class TaxResult
{
    /** @param array<int, array{name: string, rate: float, amount: float}> $breakdown */
    public function __construct(
        public float $total,
        public array $breakdown,
    ) {}

    public function getTotal(): float
    {
        return $this->total;
    }

    /** @return array<int, array{name: string, rate: float, amount: float}> */
    public function getBreakdown(): array
    {
        return $this->breakdown;
    }
}
