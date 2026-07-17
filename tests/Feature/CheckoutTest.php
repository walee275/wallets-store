<?php

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;

test('guest can place cod order with inline address and stock is decremented', function () {
    $setup = createCodCheckoutSetup();
    $variant = $setup['variant'];
    $initialStock = $variant->stock_quantity;

    $cartResponse = $this->post(route('cart.store'), [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    carrySessionFrom($cartResponse);

    $shippingResponse = $this->post(route('checkout.shipping'), [
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    carrySessionFrom($shippingResponse);

    $response = $this->post(route('checkout.place'), [
        'email' => 'guest@commerce.test',
        'payment_driver' => 'cod',
        'billing_same_as_shipping' => true,
        'shipping_rate_id' => $setup['shippingRate']->id,
        ...sampleCheckoutAddress(),
    ])->assertSessionHasNoErrors();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->email)->toBe('guest@commerce.test')
        ->and($order->status)->toBe(OrderStatus::Processing)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($order->shipping_address_json['line1'])->toBe('123 Main Street');

    $response->assertRedirect(route('checkout.confirmation', $order));

    expect($variant->fresh()->stock_quantity)->toBe($initialStock - 2);
});

test('authenticated user can save address for future when placing order', function () {
    $setup = createCodCheckoutSetup();
    $customer = createCustomerUser();

    $cartResponse = $this->actingAs($customer)->post(route('cart.store'), [
        'variant_id' => $setup['variant']->id,
        'quantity' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    carrySessionFrom($cartResponse);

    $shippingResponse = $this->actingAs($customer)->post(route('checkout.shipping'), [
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    carrySessionFrom($shippingResponse);

    $this->actingAs($customer)->post(route('checkout.place'), [
        'payment_driver' => 'cod',
        'billing_same_as_shipping' => true,
        'shipping_rate_id' => $setup['shippingRate']->id,
        'save_address_for_future' => true,
        ...sampleCheckoutAddress(),
    ])->assertSessionHasNoErrors();

    $address = Address::query()->where('user_id', $customer->id)->first();

    expect($address)->not->toBeNull()
        ->and($address->name)->toBe('Test Customer')
        ->and($address->line1)->toBe('123 Main Street')
        ->and($address->city)->toBe('Lahore')
        ->and($address->is_default)->toBeTrue();
});

test('place order requires shipping address fields', function () {
    $setup = createCodCheckoutSetup();

    $cartResponse = $this->post(route('cart.store'), [
        'variant_id' => $setup['variant']->id,
        'quantity' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    carrySessionFrom($cartResponse);

    $shippingResponse = $this->post(route('checkout.shipping'), [
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    carrySessionFrom($shippingResponse);

    $this->post(route('checkout.place'), [
        'email' => 'guest@commerce.test',
        'payment_driver' => 'cod',
        'billing_same_as_shipping' => true,
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertSessionHasErrors(['name', 'line1', 'city', 'country']);

    expect(Order::query()->count())->toBe(0);
});
