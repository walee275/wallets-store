<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class CodGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'cod';
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        return new PaymentInitiation(
            status: 'pending_cod',
            providerRef: 'cod-'.$payment->id,
            meta: [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ],
        );
    }

    public function handleWebhook(Request $request): void
    {
        // Cash on delivery has no external webhook.
    }

    public function refund(Payment $payment, int $amountCents): void
    {
        // COD refunds are handled offline; order/payment status is updated by the caller.
    }
}
