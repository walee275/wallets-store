<?php

namespace App\Actions\Cart;

use App\Models\CartItem;

class UpdateCartItem
{
    public function handle(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->loadMissing('variant');
        $variant = $item->variant;

        if ($variant !== null) {
            $quantity = min($quantity, $variant->stock_quantity);
        }

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity]);
    }
}
