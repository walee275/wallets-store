<?php

use App\Enums\OrderStatus;
use App\Models\Order;

test('guest can place cod order and stock is decremented', function () {
    $setup = createCodCheckoutSetup();
    $variant = $setup['variant'];
    $initialStock = $variant->stock_quantity;

    $cartResponse = $this->post(route('cart.store'), [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    carrySessionFrom($cartResponse);

    $addressResponse = $this->post(route('checkout.address'), sampleCheckoutAddress())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    carrySessionFrom($addressResponse);

    $shippingResponse = $this->post(route('checkout.shipping'), [
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    carrySessionFrom($shippingResponse);

    $response = $this->post(route('checkout.place'), [
        'email' => 'guest@commerce.test',
        'payment_driver' => 'cod',
        'billing_same_as_shipping' => true,
    ])->assertSessionHasNoErrors();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->email)->toBe('guest@commerce.test')
        ->and($order->status)->toBe(OrderStatus::Processing)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2);

    $response->assertRedirect(route('checkout.confirmation', $order));

    expect($variant->fresh()->stock_quantity)->toBe($initialStock - 2);
});
