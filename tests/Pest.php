<?php

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function carrySessionFrom(TestResponse $response): void
{
    $cookie = $response->getCookie(config('session.cookie'), false);

    if ($cookie !== null) {
        test()->withCookie($cookie->getName(), $cookie->getValue());
    }
}

function createCheckoutProduct(int $priceCents = 150000, int $stock = 50): ProductVariant
{
    $product = Product::factory()->create([
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = $product->variants()->first();
    $variant->update([
        'price_cents' => $priceCents,
        'stock_quantity' => $stock,
        'is_active' => true,
        'is_default' => true,
    ]);

    return $variant->fresh();
}

function createCodCheckoutSetup(): array
{
    PaymentMethod::query()->create([
        'driver' => 'cod',
        'name' => 'Cash on Delivery',
        'is_enabled' => true,
        'sort_order' => 1,
    ]);

    $zone = ShippingZone::query()->create([
        'name' => 'Pakistan',
        'countries_json' => ['PK'],
        'is_active' => true,
    ]);

    $rate = ShippingRate::query()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'price_cents' => 25000,
        'is_active' => true,
    ]);

    return [
        'variant' => createCheckoutProduct(),
        'shippingRate' => $rate,
    ];
}

function sampleCheckoutAddress(): array
{
    return [
        'name' => 'Test Customer',
        'phone' => '03001234567',
        'line1' => '123 Main Street',
        'line2' => null,
        'city' => 'Lahore',
        'state' => 'Punjab',
        'postal_code' => '54000',
        'country' => 'PK',
    ];
}

function createJazzCashPendingPayment(): Payment
{
    PaymentMethod::query()->create([
        'driver' => 'jazzcash',
        'name' => 'JazzCash',
        'is_enabled' => true,
        'sort_order' => 1,
    ]);

    $variant = createCheckoutProduct();

    $order = Order::query()->create([
        'number' => 'ORD-TEST-0001',
        'email' => 'guest@commerce.test',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Pending,
        'currency' => 'PKR',
        'subtotal_cents' => $variant->price_cents,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => $variant->price_cents,
        'placed_at' => now(),
    ]);

    return Payment::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => PaymentMethod::query()->where('driver', 'jazzcash')->value('id'),
        'provider' => 'jazzcash',
        'amount_cents' => $order->total_cents,
        'currency' => 'PKR',
        'status' => PaymentStatus::Pending,
        'idempotency_key' => 'test-jazzcash-'.uniqid(),
    ]);
}

function createActiveDiscount(string $code, DiscountType $type, int $value = 0, ?int $minOrderCents = null): Discount
{
    return Discount::query()->create([
        'code' => $code,
        'type' => $type,
        'value' => $value,
        'min_order_cents' => $minOrderCents,
        'is_active' => true,
    ]);
}

function createAdminUser(): User
{
    return User::factory()->admin()->create([
        'email' => 'admin-test@commerce.test',
        'is_active' => true,
    ]);
}

function createCustomerUser(): User
{
    return User::factory()->create([
        'email' => 'customer-test@commerce.test',
        'is_active' => true,
    ]);
}
