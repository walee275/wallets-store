<?php

namespace App\Actions\Checkout;

use App\Models\Discount;
use App\Models\DiscountRedemption;
use App\Models\User;
use InvalidArgumentException;

class ApplyDiscount
{
    public function handle(string $code, int $subtotalCents, ?User $user = null): Discount
    {
        $discount = Discount::query()
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->where('is_active', true)
            ->first();

        if ($discount === null) {
            throw new InvalidArgumentException('Discount code is invalid or inactive.');
        }

        $now = now();

        if ($discount->starts_at !== null && $now->lt($discount->starts_at)) {
            throw new InvalidArgumentException('This discount code is not yet active.');
        }

        if ($discount->ends_at !== null && $now->gt($discount->ends_at)) {
            throw new InvalidArgumentException('This discount code has expired.');
        }

        if ($discount->max_uses !== null) {
            $totalRedemptions = DiscountRedemption::query()
                ->where('discount_id', $discount->id)
                ->count();

            if ($totalRedemptions >= $discount->max_uses) {
                throw new InvalidArgumentException('This discount code has reached its usage limit.');
            }
        }

        if ($user !== null && $discount->max_uses_per_user !== null) {
            $userRedemptions = DiscountRedemption::query()
                ->where('discount_id', $discount->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userRedemptions >= $discount->max_uses_per_user) {
                throw new InvalidArgumentException('You have already used this discount code the maximum number of times.');
            }
        }

        if ($discount->min_order_cents !== null && $subtotalCents < $discount->min_order_cents) {
            throw new InvalidArgumentException('Order subtotal does not meet the discount minimum.');
        }

        return $discount;
    }
}
