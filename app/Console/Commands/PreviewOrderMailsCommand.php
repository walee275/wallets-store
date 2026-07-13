<?php

namespace App\Console\Commands;

use App\Mail\OrderConfirmedMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Support\ProductEmailImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PreviewOrderMailsCommand extends Command
{
    protected $signature = 'mail:preview
                            {--order= : Use a real order ID from the database}
                            {--open : Print local browser preview URLs}';

    protected $description = 'Render Atelier order emails to storage/app/mail-previews for visual QA';

    public function handle(): int
    {
        $order = $this->resolveOrder();

        $this->ensureEmailThumbs($order);

        $destination = storage_path('app/mail-previews');
        File::ensureDirectoryExists($destination);

        $mailables = [
            'order-confirmed' => new OrderConfirmedMail($order),
            'order-shipped' => new OrderShippedMail($order),
            'order-delivered' => new OrderDeliveredMail($order, reviewUrl: url('/')),
            'payment-failed' => new PaymentFailedMail($order, retryUrl: route('checkout.confirmation', $order)),
        ];

        $this->info('Rendering emails for order '.$order->number.'…');
        $this->newLine();

        foreach ($mailables as $name => $mailable) {
            $html = $mailable->render();
            $path = $destination.'/'.$name.'.html';
            File::put($path, $html);

            $this->line("  · {$name}");
            $this->line("    file://{$path}");

            if ($this->option('open') && app()->environment('local')) {
                $this->line('    '.url("/mail-preview/{$name}"));
            }
        }

        $this->newLine();
        $this->comment('Tip: on Forge/Cloud, download these HTML files or open the file:// paths locally.');
        $this->comment('Locally, browse /mail-preview/{type} (local env only) for live CDN image checks.');

        return self::SUCCESS;
    }

    protected function resolveOrder(): Order
    {
        if ($orderId = $this->option('order')) {
            $order = Order::query()
                ->with(['items.variant.images', 'items.variant.product.images', 'payments'])
                ->findOrFail($orderId);

            if (! $order->tracking_number) {
                $order->forceFill([
                    'carrier' => $order->carrier ?: 'TCS',
                    'tracking_number' => $order->tracking_number ?: 'TCS-994102',
                    'tracking_url' => $order->tracking_url ?: 'https://www.tcsexpress.com/tracking',
                ]);
            }

            return $order;
        }

        $order = Order::query()
            ->with(['items.variant.images', 'items.variant.product.images', 'payments'])
            ->latest('id')
            ->first();

        if ($order) {
            $order->forceFill([
                'carrier' => $order->carrier ?: 'TCS',
                'tracking_number' => $order->tracking_number ?: 'TCS-994102',
                'tracking_url' => $order->tracking_url ?: 'https://www.tcsexpress.com/tracking',
            ]);

            return $order;
        }

        return $this->buildSampleOrder();
    }

    protected function buildSampleOrder(): Order
    {
        $variants = ProductVariant::query()
            ->with(['product.images', 'images', 'optionValues.optionType'])
            ->where('is_active', true)
            ->limit(2)
            ->get();

        if ($variants->isEmpty()) {
            $this->warn('No products found — sample order will have no line items or images.');
        }

        $order = new Order([
            'id' => 0,
            'number' => 'HC-'.now()->format('Ymd').'-0142',
            'email' => 'sarah@example.com',
            'currency' => config('store.currency', 'PKR'),
            'subtotal_cents' => 0,
            'discount_cents' => 165000,
            'shipping_cents' => 30000,
            'tax_cents' => 0,
            'total_cents' => 0,
            'discount_code' => 'WELCOME10',
            'carrier' => 'TCS',
            'tracking_number' => 'TCS-994102',
            'tracking_url' => 'https://www.tcsexpress.com/tracking',
            'shipping_address_json' => [
                'name' => 'Sarah Ahmed',
                'phone' => '03001234567',
                'line1' => 'House 12, Street 4, F-8/3',
                'line2' => null,
                'city' => 'Islamabad',
                'state' => 'ICT',
                'postal_code' => '44000',
                'country' => 'Pakistan',
            ],
        ]);

        $items = $variants->values()->map(function (ProductVariant $variant, int $index) {
            $qty = $index === 0 ? 1 : 2;
            $options = $variant->optionValues
                ->map(fn ($value) => [
                    'type' => $value->optionType?->name ?? 'Option',
                    'value' => $value->value,
                ])
                ->values()
                ->all();

            if ($options === []) {
                $options = [
                    ['type' => 'Color', 'value' => 'Cognac'],
                    ['type' => 'Material', 'value' => 'Full-Grain'],
                ];
            }

            $item = new OrderItem([
                'product_title' => $variant->product?->title ?? 'Leather Good',
                'variant_sku' => $variant->sku,
                'options_json' => $options,
                'quantity' => $qty,
                'unit_price_cents' => $variant->price_cents,
                'total_cents' => $variant->price_cents * $qty,
            ]);
            $item->setRelation('variant', $variant);

            return $item;
        });

        $subtotal = $items->sum('total_cents');
        $order->subtotal_cents = $subtotal;
        $order->total_cents = max(0, $subtotal + $order->shipping_cents - $order->discount_cents);
        $order->setRelation('items', $items);
        $order->setRelation('payments', collect());

        // route() helpers need a resolvable key — use a temporary id for URLs only
        $order->id = Order::query()->max('id') + 1 ?: 1;

        return $order;
    }

    protected function ensureEmailThumbs(Order $order): void
    {
        foreach ($order->items as $item) {
            $variant = $item->variant;

            if (! $variant instanceof ProductVariant) {
                continue;
            }

            $image = ProductEmailImage::resolvePrimaryImage($variant);

            if ($image && $image->path) {
                ProductEmailImage::generateThumb($image);
                $this->line('  thumb → '.($image->fresh()->email_thumb_path ?? 'n/a'));
            }
        }
    }
}
