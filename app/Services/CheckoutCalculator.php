<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CheckoutCalculator
{
    /**
     * @param  Collection<int, object{quantity: int, unit_price_cents: int}>  $items
     * @return array{
     *     subtotal_cents: int,
     *     discount_cents: int,
     *     shipping_cents: int,
     *     tax_cents: int,
     *     total_cents: int
     * }
     */
    public function calculate(Collection $items, ?Discount $discount, int $shippingCents): array
    {
        $subtotalCents = (int) $items->sum(
            fn ($item) => ((int) $item->quantity) * ((int) $item->unit_price_cents)
        );

        if ($discount?->min_order_cents !== null && $subtotalCents < $discount->min_order_cents) {
            throw new InvalidArgumentException('Order subtotal does not meet the discount minimum.');
        }

        $discountCents = 0;
        $resolvedShippingCents = $shippingCents;

        if ($discount !== null) {
            $type = $discount->type instanceof DiscountType
                ? $discount->type
                : DiscountType::from((string) $discount->type);

            match ($type) {
                DiscountType::Percent => $discountCents = min(
                    (int) floor($subtotalCents * $discount->value / 100),
                    $subtotalCents
                ),
                DiscountType::Fixed => $discountCents = min((int) $discount->value, $subtotalCents),
                DiscountType::FreeShipping => $resolvedShippingCents = 0,
            };
        }

        $taxCents = 0;
        $totalCents = $subtotalCents - $discountCents + $resolvedShippingCents + $taxCents;

        return [
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'shipping_cents' => $resolvedShippingCents,
            'tax_cents' => $taxCents,
            'total_cents' => max(0, $totalCents),
        ];
    }
}
