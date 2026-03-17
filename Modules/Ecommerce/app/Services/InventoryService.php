<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Events\LowStockDetected;
use Modules\Ecommerce\Models\ProductVariant;

class InventoryService
{
    public function deductStock(ProductVariant $variant, int $qty): void
    {
        if (! config('modules.ecommerce.stock.track_inventory')) {
            return;
        }

        DB::transaction(function () use ($variant, $qty): void {
            $variant->decrement('stock', $qty);
            $variant->refresh();

            if ($this->checkLowStock($variant)) {
                LowStockDetected::dispatch($variant);
            }
        });
    }

    public function canFulfill(ProductVariant $variant, int $qty): bool
    {
        if (! config('modules.ecommerce.stock.track_inventory')) {
            return true;
        }

        return $variant->stock >= $qty || $variant->allow_backorder;
    }

    public function checkLowStock(ProductVariant $variant): bool
    {
        if (! config('modules.ecommerce.stock.track_inventory')) {
            return false;
        }

        return $variant->stock <= $variant->low_stock_threshold;
    }

    public function restoreStock(ProductVariant $variant, int $qty): void
    {
        if (! config('modules.ecommerce.stock.track_inventory')) {
            return;
        }

        $variant->increment('stock', $qty);
    }
}
