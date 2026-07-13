<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ProductEmailImage;
use Illuminate\Support\Facades\Storage;

function makeTestJpegBytes(int $size = 200): string
{
    $image = imagecreatetruecolor($size, $size);
    $color = imagecolorallocate($image, 140, 90, 43);
    imagefill($image, 0, 0, $color);
    ob_start();
    imagejpeg($image, null, 90);
    imagedestroy($image);

    return (string) ob_get_clean();
}

it('renders order confirmed mail with absolute product image urls', function () {
    $disk = Storage::disk(config('media.disk', 'public'));
    $path = 'products/test-bag.jpg';
    $disk->put($path, makeTestJpegBytes());

    $product = Product::factory()->create(['title' => 'The Foundry Bifold']);
    $variant = $product->variants()->first();
    $variant->update(['price_cents' => 890000]);

    $image = ProductImage::query()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'path' => $path,
        'alt' => 'The Foundry Bifold',
        'position' => 0,
        'is_primary' => true,
    ]);

    ProductEmailImage::generateThumb($image);

    $order = Order::query()->create([
        'number' => 'HC-TEST-0001',
        'email' => 'sarah@example.com',
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 890000,
        'discount_cents' => 0,
        'shipping_cents' => 30000,
        'tax_cents' => 0,
        'total_cents' => 920000,
        'shipping_address_json' => [
            'name' => 'Sarah Ahmed',
            'line1' => 'House 12, Street 4, F-8/3',
            'city' => 'Islamabad',
            'postal_code' => '44000',
            'country' => 'Pakistan',
        ],
        'placed_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'variant_id' => $variant->id,
        'product_title' => 'The Foundry Bifold',
        'variant_sku' => $variant->sku,
        'options_json' => [
            ['type' => 'Color', 'value' => 'Cognac'],
            ['type' => 'Material', 'value' => 'Full-Grain'],
        ],
        'quantity' => 1,
        'unit_price_cents' => 890000,
        'total_cents' => 890000,
    ]);

    $order->load(['items.variant.images', 'items.variant.product.images']);

    $html = (new OrderConfirmedMail($order))->render();

    expect($html)
        ->toContain('Order Confirmed')
        ->toContain('Your order is in good hands.')
        ->toContain('width="72"')
        ->toContain('height="72"')
        ->toContain('alt="The Foundry Bifold in Cognac Full-Grain"')
        ->toContain('PKR 8,900')
        ->toContain('v:rect')
        ->toContain('background-color:#8C5A2B');

    expect($html)->toMatch('/src="https?:\/\/[^"]+products\/email\//');
});

it('uses oxblood banner for payment failed mail', function () {
    $order = Order::query()->create([
        'number' => 'HC-TEST-0002',
        'email' => 'sarah@example.com',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Failed,
        'currency' => 'PKR',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'shipping_address_json' => ['name' => 'Sarah Ahmed', 'line1' => 'A', 'city' => 'Islamabad', 'country' => 'Pakistan'],
        'placed_at' => now(),
    ]);

    $html = (new PaymentFailedMail($order))->render();

    expect($html)
        ->toContain('background-color:#5C1F22')
        ->toContain('Payment Could Not Be Processed')
        ->toContain('Retry Payment');
});

it('includes tracking details in shipped mail', function () {
    $order = Order::query()->create([
        'number' => 'HC-TEST-0003',
        'email' => 'sarah@example.com',
        'status' => OrderStatus::Shipped,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'carrier' => 'TCS',
        'tracking_number' => 'TCS-994102',
        'tracking_url' => 'https://www.tcsexpress.com/tracking',
        'shipping_address_json' => ['name' => 'Sarah Ahmed', 'line1' => 'A', 'city' => 'Islamabad', 'country' => 'Pakistan'],
        'placed_at' => now(),
    ]);

    $html = (new OrderShippedMail($order))->render();

    expect($html)
        ->toContain('Tracking: TCS-994102')
        ->toContain('https://www.tcsexpress.com/tracking')
        ->toContain('Track Shipment');
});
