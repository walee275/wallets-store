<?php

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Services\CheckoutCalculator;
use Illuminate\Support\Collection;
use InvalidArgumentException as CartInvalidArgumentException;

test('calculates percent discount', function () {
    $calculator = new CheckoutCalculator;

    $items = Collection::make([
        (object) ['quantity' => 2, 'unit_price_cents' => 100000],
    ]);

    $discount = new Discount([
        'type' => DiscountType::Percent,
        'value' => 10,
    ]);

    $totals = $calculator->calculate($items, $discount, 25000);

    expect($totals['subtotal_cents'])->toBe(200000)
        ->and($totals['discount_cents'])->toBe(20000)
        ->and($totals['shipping_cents'])->toBe(25000)
        ->and($totals['total_cents'])->toBe(205000);
});

test('calculates fixed discount capped at subtotal', function () {
    $calculator = new CheckoutCalculator;

    $items = Collection::make([
        (object) ['quantity' => 1, 'unit_price_cents' => 50000],
    ]);

    $discount = new Discount([
        'type' => DiscountType::Fixed,
        'value' => 50000,
    ]);

    $totals = $calculator->calculate($items, $discount, 25000);

    expect($totals['discount_cents'])->toBe(50000)
        ->and($totals['total_cents'])->toBe(25000);
});

test('calculates free shipping discount', function () {
    $calculator = new CheckoutCalculator;

    $items = Collection::make([
        (object) ['quantity' => 1, 'unit_price_cents' => 150000],
    ]);

    $discount = new Discount([
        'type' => DiscountType::FreeShipping,
        'value' => 0,
    ]);

    $totals = $calculator->calculate($items, $discount, 50000);

    expect($totals['discount_cents'])->toBe(0)
        ->and($totals['shipping_cents'])->toBe(0)
        ->and($totals['total_cents'])->toBe(150000);
});

test('throws when subtotal is below discount minimum', function () {
    $calculator = new CheckoutCalculator;

    $items = Collection::make([
        (object) ['quantity' => 1, 'unit_price_cents' => 10000],
    ]);

    $discount = new Discount([
        'type' => DiscountType::Percent,
        'value' => 10,
        'min_order_cents' => 50000,
    ]);

    $calculator->calculate($items, $discount, 0);
})->throws(CartInvalidArgumentException::class, 'Order subtotal does not meet the discount minimum.');
