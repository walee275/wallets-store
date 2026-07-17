<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmedMail;
use App\Models\Collection;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Services\StoreContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function createOwnerUser()
{
    Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);

    $owner = createAdminUser();
    $owner->assignRole('owner');

    return $owner;
}

function makeBrandingLogoUpload(string $name = 'logo.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, 400, 120);
}

test('store content is cached and flushed when settings change', function () {
    StoreSetting::set('branding', ['name' => 'Cached Store']);

    $service = app(StoreContent::class);

    expect($service->branding()['name'])->toBe('Cached Store');
    expect(Cache::has(StoreContent::CACHE_KEY))->toBeTrue();

    StoreSetting::set('branding', ['name' => 'Updated Store']);

    expect(Cache::has(StoreContent::CACHE_KEY))->toBeFalse();
    expect($service->branding()['name'])->toBe('Updated Store');
});

test('homepage shares branding fields through inertia', function () {
    StoreSetting::set('branding', [
        'name' => 'Atelier Commerce',
        'tagline' => 'Hand-stitched goods',
        'location' => 'Lahore, Pakistan',
        'care_email' => 'care@commerce.test',
        'phone' => null,
        'header_logo_path' => null,
        'footer_logo_path' => null,
    ]);

    StoreSetting::set('auth', [
        'quote' => 'Leather ages with character.',
        'attribution' => 'Workshop Notes',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Home')
            ->where('store.branding.name', 'Atelier Commerce')
            ->where('store.auth.quote', 'Leather ages with character.')
            ->where('store.currency', 'PKR')
        );
});

test('homepage uses featured collection id when configured', function () {
    $featured = Collection::query()->create([
        'name' => 'Curated Carry',
        'slug' => 'curated-carry',
        'type' => 'manual',
        'is_featured' => false,
    ]);

    Collection::query()->create([
        'name' => 'Fallback Featured',
        'slug' => 'fallback-featured',
        'type' => 'manual',
        'is_featured' => true,
    ]);

    StoreSetting::set('featured_collection_id', $featured->id);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Home')
            ->where('featuredCollection.id', $featured->id)
            ->where('featuredCollection.name', 'Curated Carry')
        );
});

test('owner can update store settings and upload branding logos', function () {
    Storage::fake(config('media.disk', 'public'));

    $owner = createOwnerUser();

    $payload = [
        '_method' => 'put',
        'branding' => [
            'name' => 'Workshop Store',
            'tagline' => 'Since 2012',
            'location' => 'Islamabad, Pakistan',
            'care_email' => 'hello@workshop.test',
            'phone' => '+92 300 0000000',
            'remove_header_logo' => false,
            'remove_footer_logo' => false,
        ],
        'social' => [
            'instagram' => 'https://instagram.com/workshop',
            'facebook' => '',
            'tiktok' => '',
            'youtube' => '',
        ],
        'footer' => [
            'blurb' => 'Custom footer blurb.',
            'copyright_tagline' => 'Built to last',
            'care_links' => [
                ['label' => 'Returns', 'type' => 'page', 'value' => 'returns-policy'],
            ],
            'about_links' => [
                ['label' => 'Our story', 'type' => 'page', 'value' => 'about'],
            ],
        ],
        'homepage' => [
            'hero' => [
                'eyebrow' => 'Hero eyebrow',
                'headline' => "Line one\nLine two",
                'body' => 'Hero body copy.',
                'primary_cta_label' => 'Shop now',
                'primary_cta_url' => '/products',
                'secondary_cta_label' => 'Learn more',
                'secondary_cta_url' => '#craft',
            ],
            'banner_url' => '',
            'categories_eyebrow' => 'Browse',
            'categories_heading' => 'Categories',
            'featured_eyebrow' => 'Featured',
            'featured_heading' => 'Highlights',
            'craft_steps' => [
                ['title' => '01', 'heading' => 'Select', 'body' => 'Pick hides.'],
                ['title' => '02', 'heading' => 'Stitch', 'body' => 'Hand stitch.'],
                ['title' => '03', 'heading' => 'Finish', 'body' => 'Burnish edges.'],
            ],
        ],
        'seo' => [
            'default_description' => 'Workshop leather goods.',
            'title_suffix' => 'Workshop Store',
            'remove_og_image' => false,
        ],
        'auth' => [
            'quote' => 'Carry it daily.',
            'attribution' => 'Workshop floor',
        ],
        'currency' => 'USD',
        'tax_mode' => 'inclusive',
        'featured_collection_id' => null,
    ];

    $this->actingAs($owner)
        ->post(route('admin.settings.update'), array_merge($payload, [
            'branding' => array_merge($payload['branding'], [
                'header_logo' => makeBrandingLogoUpload(),
            ]),
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $branding = StoreSetting::get('branding');

    expect($branding['name'])->toBe('Workshop Store')
        ->and($branding['header_logo_path'])->toStartWith('branding/');

    Storage::disk(config('media.disk', 'public'))->assertExists($branding['header_logo_path']);

    expect(StoreSetting::get('currency'))->toBe('USD')
        ->and(StoreSetting::get('footer')['blurb'])->toBe('Custom footer blurb.')
        ->and(app(StoreContent::class)->branding()['name'])->toBe('Workshop Store');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('store.branding.name', 'Workshop Store')
            ->where('store.footer.blurb', 'Custom footer blurb.')
            ->where('store.homepage.hero.eyebrow', 'Hero eyebrow')
        );
});

test('order email layout uses store setting branding over config defaults', function () {
    StoreSetting::set('branding', [
        'name' => 'Email Atelier',
        'tagline' => 'Mail Tagline',
        'location' => 'Karachi, Pakistan',
        'care_email' => 'orders@atelier.test',
        'phone' => null,
        'header_logo_path' => null,
        'footer_logo_path' => null,
    ]);

    $order = Order::query()->create([
        'number' => 'HC-EMAIL-0001',
        'email' => 'customer@commerce.test',
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'currency' => 'PKR',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'shipping_address_json' => sampleCheckoutAddress(),
        'placed_at' => now(),
    ]);

    $html = (new OrderConfirmedMail($order))->render();

    expect($html)
        ->toContain('EMAIL ATELIER')
        ->toContain('Mail Tagline')
        ->toContain('orders@atelier.test')
        ->toContain('Karachi, Pakistan');
});
