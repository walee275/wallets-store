<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

test('jazzcash simulate route marks payment paid and order processing', function () {
    $payment = createJazzCashPendingPayment();
    $order = $payment->order;

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($order->status)->toBe(OrderStatus::PendingPayment);

    $this->get(route('webhooks.jazzcash.simulate', $payment))
        ->assertRedirect(route('checkout.confirmation', $order));

    $payment->refresh();
    $order->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Processing)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);
});
