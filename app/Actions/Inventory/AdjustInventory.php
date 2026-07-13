<?php

namespace App\Actions\Inventory;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdjustInventory
{
    public function handle(
        ProductVariant $variant,
        int $delta,
        string $type,
        ?int $orderId = null,
        ?int $userId = null,
        ?string $note = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $delta, $type, $orderId, $userId, $note) {
            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = $lockedVariant->stock_quantity + $delta;

            if ($type === 'sale' && $newBalance < 0) {
                throw new RuntimeException('Insufficient stock to complete sale.');
            }

            $lockedVariant->update(['stock_quantity' => $newBalance]);

            return InventoryMovement::query()->create([
                'variant_id' => $lockedVariant->id,
                'order_id' => $orderId,
                'type' => $type,
                'quantity_delta' => $delta,
                'balance_after' => $newBalance,
                'note' => $note,
                'created_by' => $userId,
            ]);
        });
    }
}
