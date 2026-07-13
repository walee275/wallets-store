<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Page;
use App\Models\PaymentMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class CommerceSettingsSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::set('currency', 'PKR');
        StoreSetting::set('store_currency', 'PKR');
        StoreSetting::set('tax_mode', 'exclusive');
        StoreSetting::set('homepage_banner_url', 'https://commerce.test/collections/bestsellers');
        StoreSetting::set('homepage', [
            'text' => 'Summer Sale — Up to 20% Off Bestsellers',
            'url' => '/products?collection=bestsellers',
        ]);

        $paymentMethods = [
            ['driver' => 'stripe', 'name' => 'Stripe', 'is_enabled' => false, 'sort_order' => 1],
            ['driver' => 'jazzcash', 'name' => 'JazzCash', 'is_enabled' => false, 'sort_order' => 2],
            ['driver' => 'easypaisa', 'name' => 'Easypaisa', 'is_enabled' => false, 'sort_order' => 3],
            ['driver' => 'cod', 'name' => 'Cash on Delivery', 'is_enabled' => true, 'sort_order' => 4],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['driver' => $method['driver']],
                [
                    'name' => $method['name'],
                    'is_enabled' => $method['is_enabled'],
                    'sort_order' => $method['sort_order'],
                    'config_json' => null,
                ],
            );
        }

        $zone = ShippingZone::query()->updateOrCreate(
            ['name' => 'Pakistan'],
            [
                'countries_json' => ['PK'],
                'is_active' => true,
            ],
        );

        ShippingRate::query()->updateOrCreate(
            ['zone_id' => $zone->id, 'name' => 'Standard'],
            [
                'price_cents' => 25000,
                'is_active' => true,
            ],
        );

        ShippingRate::query()->updateOrCreate(
            ['zone_id' => $zone->id, 'name' => 'Express'],
            [
                'price_cents' => 50000,
                'is_active' => true,
            ],
        );

        $discounts = [
            [
                'code' => 'SAVE10',
                'type' => DiscountType::Percent,
                'value' => 10,
            ],
            [
                'code' => 'WELCOME',
                'type' => DiscountType::Fixed,
                'value' => 50000,
            ],
            [
                'code' => 'FREESHIP',
                'type' => DiscountType::FreeShipping,
                'value' => 0,
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::query()->updateOrCreate(
                ['code' => $discount['code']],
                [
                    'type' => $discount['type'],
                    'value' => $discount['value'],
                    'is_active' => true,
                ],
            );
        }

        Page::query()->updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About',
                'body' => '<p>Commerce is a modern Laravel storefront for the Pakistani market.</p>',
                'is_published' => true,
            ],
        );

        Page::query()->updateOrCreate(
            ['slug' => 'returns-policy'],
            [
                'title' => 'Returns Policy',
                'body' => '<p>Items may be returned within 14 days of delivery in original condition.</p>',
                'is_published' => true,
            ],
        );
    }
}
