<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Refund;

class RefundService
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    public function requestRefund(Order $order, float $amount, ?string $reason = null): Refund
    {
        return Refund::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function approveRefund(Refund $refund, User $admin, ?string $notes = null): Refund
    {
        return DB::transaction(function () use ($refund, $admin, $notes) {
            $refund->update([
                'status' => 'approved',
                'notes' => $notes,
                'processed_at' => now(),
                'processed_by' => $admin->id,
            ]);

            // Restore stock for all order items
            $refund->load('order.items.variant');

            foreach ($refund->order->items as $item) {
                if ($item->variant) {
                    $this->inventoryService->restoreStock($item->variant, (int) $item->quantity);
                }
            }

            return $refund;
        });
    }

    public function rejectRefund(Refund $refund, User $admin, ?string $notes = null): Refund
    {
        $refund->update([
            'status' => 'rejected',
            'notes' => $notes,
            'processed_at' => now(),
            'processed_by' => $admin->id,
        ]);

        return $refund;
    }
}
