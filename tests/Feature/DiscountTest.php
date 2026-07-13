<?php

use App\Enums\DiscountType;

test('guest can apply a valid coupon at checkout', function () {
    $setup = createCodCheckoutSetup();

    createActiveDiscount('SAVE10', DiscountType::Percent, 10);

    $this->post(route('cart.store'), [
        'variant_id' => $setup['variant']->id,
        'quantity' => 1,
    ])->assertRedirect();

    $this->post(route('checkout.coupon'), [
        'code' => 'SAVE10',
    ])->assertRedirect()
        ->assertSessionHas('success', 'Discount code applied.');

    expect(session('checkout.discount_code'))->toBe('SAVE10');
});

test('guest receives error for invalid coupon', function () {
    $setup = createCodCheckoutSetup();

    $this->post(route('cart.store'), [
        'variant_id' => $setup['variant']->id,
        'quantity' => 1,
    ])->assertRedirect();

    $this->post(route('checkout.coupon'), [
        'code' => 'NOTREAL',
    ])->assertRedirect()
        ->assertSessionHasErrors(['code']);
});
