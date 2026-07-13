<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MergeGuestCart
{
    public function handle(User $user, string $sessionId): void
    {
        DB::transaction(function () use ($user, $sessionId) {
            $guestCart = Cart::query()
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with('items')
                ->first();

            if ($guestCart === null || $guestCart->items->isEmpty()) {
                return;
            }

            $userCart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => $guestCart->currency ?? 'PKR'],
            );

            foreach ($guestCart->items as $guestItem) {
                $variant = ProductVariant::query()->find($guestItem->variant_id);

                if ($variant === null || ! $variant->is_active) {
                    continue;
                }

                $existingItem = $userCart->items()
                    ->where('variant_id', $guestItem->variant_id)
                    ->first();

                $mergedQuantity = ($existingItem?->quantity ?? 0) + $guestItem->quantity;
                $cappedQuantity = min($mergedQuantity, $variant->stock_quantity);

                if ($cappedQuantity <= 0) {
                    $existingItem?->delete();

                    continue;
                }

                if ($existingItem !== null) {
                    $existingItem->update(['quantity' => $cappedQuantity]);
                } else {
                    $userCart->items()->create([
                        'variant_id' => $guestItem->variant_id,
                        'quantity' => $cappedQuantity,
                    ]);
                }
            }

            $guestCart->items()->delete();
            $guestCart->delete();
        });
    }
}
