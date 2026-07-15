<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

test('placing an order queues customer confirmation and admin notification', function () {
    Mail::fake();

    $admin = createAdminUser();
    $setup = createCodCheckoutSetup();
    $variant = $setup['variant'];

    $cartResponse = $this->post(route('cart.store'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    carrySessionFrom($cartResponse);

    $addressResponse = $this->post(route('checkout.address'), sampleCheckoutAddress())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    carrySessionFrom($addressResponse);

    $shippingResponse = $this->post(route('checkout.shipping'), [
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    carrySessionFrom($shippingResponse);

    $this->post(route('checkout.place'), [
        'email' => 'guest@commerce.test',
        'payment_driver' => 'cod',
        'billing_same_as_shipping' => true,
        'shipping_rate_id' => $setup['shippingRate']->id,
    ])->assertSessionHasNoErrors();

    $order = Order::query()->first();

    expect($order)->not->toBeNull();

    Mail::assertQueued(OrderConfirmedMail::class, function (OrderConfirmedMail $mail) use ($order) {
        return $mail->order->is($order) && $mail->hasTo('guest@commerce.test');
    });

    Mail::assertQueued(NewOrderAdminMail::class, function (NewOrderAdminMail $mail) use ($order, $admin) {
        return $mail->order->is($order) && $mail->hasTo($admin->email);
    });
});

test('marking an order shipped saves tracking details and queues shipped mail', function () {
    Mail::fake();

    $admin = createAdminUser();

    $order = Order::query()->create([
        'number' => 'HC-NOTIFY-0001',
        'email' => 'customer@commerce.test',
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'shipping_address_json' => sampleCheckoutAddress(),
        'placed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.update-status', $order), [
            'status' => OrderStatus::Shipped->value,
            'carrier' => 'TCS',
            'tracking_number' => 'TCS-994102',
            'tracking_url' => 'https://www.tcsexpress.com/tracking',
            'note' => 'Left the atelier',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Shipped)
        ->and($order->carrier)->toBe('TCS')
        ->and($order->tracking_number)->toBe('TCS-994102')
        ->and($order->tracking_url)->toBe('https://www.tcsexpress.com/tracking');

    Mail::assertQueued(OrderShippedMail::class, function (OrderShippedMail $mail) use ($order) {
        return $mail->order->is($order) && $mail->hasTo('customer@commerce.test');
    });

    Mail::assertNotQueued(OrderDeliveredMail::class);
});

test('marking an order delivered queues delivered mail', function () {
    Mail::fake();

    $admin = createAdminUser();

    $order = Order::query()->create([
        'number' => 'HC-NOTIFY-0002',
        'email' => 'customer@commerce.test',
        'status' => OrderStatus::Shipped,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'shipping_address_json' => sampleCheckoutAddress(),
        'placed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.update-status', $order), [
            'status' => OrderStatus::Delivered->value,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Mail::assertQueued(OrderDeliveredMail::class, function (OrderDeliveredMail $mail) use ($order) {
        return $mail->order->is($order) && $mail->hasTo('customer@commerce.test');
    });

    Mail::assertNotQueued(OrderShippedMail::class);
});

test('updating status to processing does not queue status emails', function () {
    Mail::fake();

    $admin = createAdminUser();

    $order = Order::query()->create([
        'number' => 'HC-NOTIFY-0003',
        'email' => 'customer@commerce.test',
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'shipping_address_json' => sampleCheckoutAddress(),
        'placed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.update-status', $order), [
            'status' => OrderStatus::Processing->value,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Mail::assertNotQueued(OrderShippedMail::class);
    Mail::assertNotQueued(OrderDeliveredMail::class);
    Mail::assertNotQueued(OrderConfirmedMail::class);
    Mail::assertNotQueued(NewOrderAdminMail::class);
});

it('renders new order admin mail with admin banner and cta', function () {
    $order = Order::query()->create([
        'number' => 'HC-NOTIFY-0004',
        'email' => 'customer@commerce.test',
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 150000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 150000,
        'shipping_address_json' => [
            'name' => 'Sarah Ahmed',
            'line1' => 'A',
            'city' => 'Islamabad',
            'country' => 'Pakistan',
        ],
        'placed_at' => now(),
    ]);

    $html = (new NewOrderAdminMail($order))->render();

    expect($html)
        ->toContain('New Order Received')
        ->toContain('A new order just came in.')
        ->toContain('HC-NOTIFY-0004')
        ->toContain('View Order')
        ->toContain('background-color:#2A1D16');
});
