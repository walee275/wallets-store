<?php

use App\Models\Cart;

test('guest can add to cart, update quantity, and view cart page', function () {
    $setup = createCodCheckoutSetup();
    $variant = $setup['variant'];

    $postResponse = $this->post(route('cart.store'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $cart = Cart::query()->whereHas('items')->first();
    $item = $cart?->items()->first();

    expect($item)->not->toBeNull()
        ->and($item->quantity)->toBe(1);

    carrySessionFrom($postResponse);

    $this->patch(route('cart.update', $item), [
        'quantity' => 3,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($item->fresh()->quantity)->toBe(3);

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Cart/Index')
            ->has('cart.items', 1)
        );
});
