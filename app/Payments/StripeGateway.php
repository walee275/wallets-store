<?php

namespace App\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'stripe';
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $order->loadMissing('items');

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency' => strtolower($order->currency),
                'product_data' => [
                    'name' => $item->product_title,
                ],
                'unit_amount' => $item->unit_price_cents,
            ],
            'quantity' => $item->quantity,
        ])->values()->all();

        $session = Session::create([
            'mode' => 'payment',
            'success_url' => config('services.stripe.success_url').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('services.stripe.cancel_url'),
            'line_items' => $lineItems,
            'metadata' => [
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
            ],
            'client_reference_id' => (string) $order->id,
        ]);

        $payment->update([
            'provider_ref' => $session->id,
            'raw_payload_json' => [
                'checkout_session_id' => $session->id,
            ],
        ]);

        return new PaymentInitiation(
            status: 'redirect',
            redirectUrl: $session->url,
            providerRef: $session->id,
            meta: [
                'session_id' => $session->id,
            ],
        );
    }

    public function handleWebhook(Request $request): void
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            abort(400, 'Invalid Stripe webhook payload.');
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
            default => null,
        };
    }

    public function refund(Payment $payment, int $amountCents): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $paymentIntentId = $this->resolvePaymentIntentId($payment);

        Refund::create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
        ]);

        $isFullRefund = $amountCents >= $payment->amount_cents;

        $payment->update([
            'status' => $isFullRefund
                ? PaymentStatus::Refunded
                : PaymentStatus::PartiallyRefunded,
        ]);
    }

    protected function handleCheckoutSessionCompleted(object $session): void
    {
        $paymentId = $session->metadata->payment_id ?? null;

        if (! $paymentId) {
            return;
        }

        $payment = Payment::query()->find($paymentId);

        if (! $payment) {
            return;
        }

        $payload = array_merge($payment->raw_payload_json ?? [], [
            'checkout_session_id' => $session->id,
            'payment_intent_id' => $session->payment_intent,
        ]);

        $payment->update(['raw_payload_json' => $payload]);

        $this->markPaymentPaid($payment);
    }

    protected function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        $payment = Payment::query()
            ->where('provider', $this->driver())
            ->where(function ($query) use ($paymentIntent) {
                $query->where('provider_ref', $paymentIntent->id)
                    ->orWhere('raw_payload_json->payment_intent_id', $paymentIntent->id);
            })
            ->first();

        if (! $payment) {
            $paymentId = $paymentIntent->metadata->payment_id ?? null;

            if ($paymentId) {
                $payment = Payment::query()->find($paymentId);
            }
        }

        if (! $payment) {
            return;
        }

        $payload = array_merge($payment->raw_payload_json ?? [], [
            'payment_intent_id' => $paymentIntent->id,
        ]);

        $payment->update(['raw_payload_json' => $payload]);

        $this->markPaymentPaid($payment);
    }

    protected function markPaymentPaid(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $payment->refresh();

            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            $payment->order()->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
            ]);
        });
    }

    protected function resolvePaymentIntentId(Payment $payment): string
    {
        $paymentIntentId = $payment->raw_payload_json['payment_intent_id'] ?? null;

        if ($paymentIntentId) {
            return $paymentIntentId;
        }

        $session = Session::retrieve($payment->provider_ref);

        return $session->payment_intent;
    }
}
