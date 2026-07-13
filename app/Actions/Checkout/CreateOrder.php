<?php

namespace App\Actions\Checkout;

use App\Actions\Inventory\AdjustInventory;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendOrderConfirmation;
use App\Models\Cart;
use App\Models\DiscountRedemption;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymentInitiation;
use App\Services\CheckoutCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class CreateOrder
{
    public function __construct(
        protected ApplyDiscount $applyDiscount,
        protected CheckoutCalculator $checkoutCalculator,
        protected AdjustInventory $adjustInventory,
        protected PaymentGatewayManager $paymentGatewayManager,
    ) {}

    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>  $billingAddress
     * @return array{order: Order, payment: Payment, initiation: PaymentInitiation}
     */
    public function handle(
        Cart $cart,
        array $shippingAddress,
        array $billingAddress,
        string $email,
        ?User $user,
        int $shippingRateId,
        string $paymentDriver,
        ?string $discountCode = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use (
            $cart,
            $shippingAddress,
            $billingAddress,
            $email,
            $user,
            $shippingRateId,
            $paymentDriver,
            $discountCode,
            $notes,
        ) {
            $cart = Cart::query()
                ->whereKey($cart->id)
                ->with(['items.variant.product', 'items.variant.optionValues.optionType'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($cart->items->isEmpty()) {
                throw new InvalidArgumentException('Cart is empty.');
            }

            $lockedVariants = [];

            foreach ($cart->items as $item) {
                $variant = ProductVariant::query()
                    ->whereKey($item->variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($variant === null || ! $variant->is_active) {
                    throw new InvalidArgumentException('One or more cart items are no longer available.');
                }

                if ($item->quantity > $variant->stock_quantity) {
                    throw new RuntimeException("Insufficient stock for SKU {$variant->sku}.");
                }

                $lockedVariants[$variant->id] = $variant;
            }

            $shippingRate = ShippingRate::query()
                ->whereKey($shippingRateId)
                ->where('is_active', true)
                ->first();

            if ($shippingRate === null) {
                throw new InvalidArgumentException('Selected shipping rate is not available.');
            }

            $calculatorItems = $cart->items->map(fn ($item) => (object) [
                'quantity' => $item->quantity,
                'unit_price_cents' => $lockedVariants[$item->variant_id]->price_cents,
            ]);

            $subtotalCents = (int) $calculatorItems->sum(
                fn ($item) => $item->quantity * $item->unit_price_cents
            );

            $discount = null;

            if ($discountCode !== null && $discountCode !== '') {
                $discount = $this->applyDiscount->handle($discountCode, $subtotalCents, $user);
            }

            $totals = $this->checkoutCalculator->calculate(
                $calculatorItems,
                $discount,
                $shippingRate->price_cents,
            );

            $paymentMethod = PaymentMethod::query()
                ->where('driver', strtolower($paymentDriver))
                ->where('is_enabled', true)
                ->first();

            if ($paymentMethod === null) {
                throw new InvalidArgumentException('Selected payment method is not available.');
            }

            $order = Order::query()->create([
                'number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'email' => $email,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'currency' => $cart->currency ?? 'PKR',
                'subtotal_cents' => $totals['subtotal_cents'],
                'discount_cents' => $totals['discount_cents'],
                'shipping_cents' => $totals['shipping_cents'],
                'tax_cents' => $totals['tax_cents'],
                'total_cents' => $totals['total_cents'],
                'discount_id' => $discount?->id,
                'shipping_rate_id' => $shippingRate->id,
                'shipping_address_json' => $shippingAddress,
                'billing_address_json' => $billingAddress,
                'notes' => $notes,
                'discount_code' => $discount?->code,
                'placed_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                $variant = $lockedVariants[$item->variant_id];
                $variant->loadMissing('optionValues.optionType', 'product');

                $options = $variant->optionValues
                    ->map(fn ($optionValue) => [
                        'type' => $optionValue->optionType->name,
                        'value' => $optionValue->value,
                    ])
                    ->values()
                    ->all();

                $order->items()->create([
                    'variant_id' => $variant->id,
                    'product_title' => $variant->product->title,
                    'variant_sku' => $variant->sku,
                    'options_json' => $options,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $variant->price_cents,
                    'total_cents' => $item->quantity * $variant->price_cents,
                ]);

                $this->adjustInventory->handle(
                    $variant,
                    -$item->quantity,
                    'sale',
                    $order->id,
                    $user?->id,
                    "Order {$order->number}",
                );
            }

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'provider' => strtolower($paymentDriver),
                'amount_cents' => $order->total_cents,
                'currency' => $order->currency,
                'status' => PaymentStatus::Pending,
                'idempotency_key' => $order->id.'-'.Str::uuid()->toString(),
            ]);

            $initiation = $this->paymentGatewayManager
                ->gateway($paymentDriver)
                ->initiate($order, $payment);

            if ($paymentDriver === 'cod' || $initiation->status === 'pending_cod') {
                $order->update([
                    'status' => OrderStatus::Processing,
                    'payment_status' => PaymentStatus::Pending,
                ]);
            } else {
                $order->update([
                    'status' => OrderStatus::PendingPayment,
                    'payment_status' => PaymentStatus::Pending,
                ]);
            }

            $cart->items()->delete();

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => $order->status->value,
                'note' => 'Order placed',
                'changed_by' => $user?->id,
            ]);

            if ($discount !== null) {
                DiscountRedemption::query()->create([
                    'discount_id' => $discount->id,
                    'user_id' => $user?->id,
                    'order_id' => $order->id,
                    'redeemed_at' => now(),
                ]);
            }

            SendOrderConfirmation::dispatch($order);

            return [
                'order' => $order->fresh(['items', 'payments']),
                'payment' => $payment->fresh(),
                'initiation' => $initiation,
            ];
        });
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
