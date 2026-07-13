<?php

namespace App\Actions\Checkout;

use App\Actions\Inventory\AdjustInventory;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrders
{
    public function __construct(
        protected AdjustInventory $adjustInventory,
    ) {}

    public function handle(int $minutes = 60): int
    {
        $expiredCount = 0;

        $orders = Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->where('payment_status', PaymentStatus::Pending)
            ->where('placed_at', '<=', now()->subMinutes($minutes))
            ->with('items')
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, &$expiredCount) {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedOrder === null) {
                    return;
                }

                if ($lockedOrder->status !== OrderStatus::PendingPayment
                    || $lockedOrder->payment_status !== PaymentStatus::Pending) {
                    return;
                }

                foreach ($lockedOrder->items as $item) {
                    if ($item->variant_id === null) {
                        continue;
                    }

                    $variant = $item->variant()->lockForUpdate()->first();

                    if ($variant === null) {
                        continue;
                    }

                    $this->adjustInventory->handle(
                        $variant,
                        $item->quantity,
                        'restock',
                        $lockedOrder->id,
                        null,
                        "Unpaid order {$lockedOrder->number} expired",
                    );
                }

                $previousStatus = $lockedOrder->status;

                $lockedOrder->update([
                    'status' => OrderStatus::Cancelled,
                    'payment_status' => PaymentStatus::Failed,
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => $previousStatus->value,
                    'to_status' => OrderStatus::Cancelled->value,
                    'note' => 'Order expired due to unpaid timeout',
                    'changed_by' => null,
                ]);

                $expiredCount++;
            });
        }

        return $expiredCount;
    }
}
