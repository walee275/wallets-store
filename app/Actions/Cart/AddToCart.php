<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use InvalidArgumentException;

class AddToCart
{
    public function handle(Cart $cart, int $variantId, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $variant = ProductVariant::query()->find($variantId);

        if ($variant === null || ! $variant->is_active) {
            throw new InvalidArgumentException('This product variant is not available.');
        }

        $cartItem = $cart->items()->where('variant_id', $variantId)->first();
        $newQuantity = ($cartItem?->quantity ?? 0) + $quantity;

        if ($newQuantity > $variant->stock_quantity) {
            throw new InvalidArgumentException('Insufficient stock available.');
        }

        if ($cartItem !== null) {
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cartItem = $cart->items()->create([
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        return $cartItem->load('variant.product.images');
    }
}
