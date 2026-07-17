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
        StoreSetting::set('tax_mode', 'exclusive');

        StoreSetting::set('branding', [
            'name' => config('store.name', config('app.name', 'Commerce')),
            'tagline' => config('store.tagline', 'Est. Handcrafted Goods'),
            'location' => config('store.location', 'Islamabad, Pakistan'),
            'care_email' => config('store.care_email', config('mail.from.address', 'hello@example.com')),
            'phone' => null,
            'header_logo_path' => null,
            'footer_logo_path' => null,
        ]);

        StoreSetting::set('social', [
            'instagram' => null,
            'facebook' => null,
            'tiktok' => null,
            'youtube' => null,
        ]);

        StoreSetting::set('footer', [
            'blurb' => 'Full-grain leather goods, hand-cut and stitched in a small workshop in Islamabad. Built to be carried, not replaced.',
            'copyright_tagline' => 'Handcrafted, Not Mass-Produced',
            'care_links' => [
                ['label' => 'Returns & Exchanges', 'type' => 'page', 'value' => 'returns-policy'],
                ['label' => 'Leather Care Guide', 'type' => 'page', 'value' => 'about'],
            ],
            'about_links' => [
                ['label' => 'Our Craft', 'type' => 'page', 'value' => 'about'],
                ['label' => 'Track Order', 'type' => 'route', 'value' => 'account.orders'],
            ],
        ]);

        StoreSetting::set('homepage', [
            'hero' => [
                'eyebrow' => 'Full-Grain · Vegetable-Tanned · Hand-Stitched',
                'headline' => "Cut once.\nCarried for decades.",
                'body' => 'Every wallet begins as a single hide, selected by hand and stitched saddle-tight by our small workshop in Islamabad — built to age into something better than new.',
                'primary_cta_label' => 'Shop the Foundry Collection',
                'primary_cta_url' => '/products',
                'secondary_cta_label' => "See how it's made →",
                'secondary_cta_url' => '#craft',
            ],
            'banner_url' => null,
            'categories_eyebrow' => 'Shop by category',
            'categories_heading' => 'Three forms, one standard',
            'featured_eyebrow' => 'Most carried',
            'featured_heading' => 'Bestsellers',
            'craft_steps' => [
                [
                    'title' => '01 — Select',
                    'heading' => 'One hide, hand-chosen',
                    'body' => 'Every piece starts with a single full-grain hide, inspected for grain consistency before a single cut is made.',
                ],
                [
                    'title' => '02 — Cut & Stitch',
                    'heading' => 'Saddle-stitched by hand',
                    'body' => 'Our workshop hand-stitches every seam using waxed thread — slower than machine stitching, and far stronger.',
                ],
                [
                    'title' => '03 — Finish',
                    'heading' => 'Edges burnished, not painted',
                    'body' => 'Edges are burnished with beeswax rather than coated, so they age gracefully instead of peeling.',
                ],
            ],
        ]);

        StoreSetting::set('seo', [
            'default_description' => 'Full-grain leather goods, hand-cut and stitched in a small workshop in Islamabad.',
            'title_suffix' => config('app.name', 'Commerce'),
            'og_image_path' => null,
        ]);

        StoreSetting::set('auth', [
            'quote' => "Full-grain leather doesn't wear out — it wears in.",
            'attribution' => 'From the Workshop Floor, Islamabad',
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
