<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function driver(): string;

    public function initiate(Order $order, Payment $payment): PaymentInitiation;

    public function handleWebhook(Request $request): void;

    public function refund(Payment $payment, int $amountCents): void;
}
