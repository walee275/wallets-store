<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Cache;

class StoreContent
{
    public const CACHE_KEY = 'store.content';

    protected int $cacheTtlSeconds = 3600;

    /**
     * @return array{
     *     branding: array<string, mixed>,
     *     social: array<string, mixed>,
     *     footer: array<string, mixed>,
     *     homepage: array<string, mixed>,
     *     seo: array<string, mixed>,
     *     auth: array<string, mixed>,
     *     currency: string,
     *     tax_mode: string,
     *     featured_collection_id: int|null
     * }
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, $this->cacheTtlSeconds, fn (): array => $this->resolve());
    }

    /**
     * @return array<string, mixed>
     */
    public function branding(): array
    {
        return $this->all()['branding'];
    }

    /**
     * @return array<string, mixed>
     */
    public function homepage(): array
    {
        return $this->all()['homepage'];
    }

    /**
     * @return array<string, mixed>
     */
    public function footer(): array
    {
        return $this->all()['footer'];
    }

    /**
     * @return array<string, mixed>
     */
    public function seo(): array
    {
        return $this->all()['seo'];
    }

    /**
     * @return array<string, mixed>
     */
    public function auth(): array
    {
        return $this->all()['auth'];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolve(): array
    {
        $settings = StoreSetting::query()->pluck('value_json', 'key')->all();

        $branding = $this->resolveBranding($settings);
        $seo = $this->resolveSeo($settings);

        return [
            'branding' => $branding,
            'social' => $this->resolveSocial($settings),
            'footer' => $this->resolveFooter($settings),
            'homepage' => $this->resolveHomepage($settings),
            'seo' => $seo,
            'auth' => $this->resolveAuth($settings),
            'currency' => $this->resolveCurrency($settings),
            'tax_mode' => (string) ($settings['tax_mode'] ?? 'exclusive'),
            'featured_collection_id' => isset($settings['featured_collection_id'])
                ? (int) $settings['featured_collection_id']
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveBranding(array $settings): array
    {
        $defaults = $this->defaultBranding();
        $stored = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];

        $branding = array_merge($defaults, array_filter($stored, fn ($value) => $value !== null && $value !== ''));

        $headerLogoPath = $branding['header_logo_path'] ?? null;
        $footerLogoPath = $branding['footer_logo_path'] ?? null;

        return [
            'name' => (string) ($branding['name'] ?? $defaults['name']),
            'tagline' => (string) ($branding['tagline'] ?? $defaults['tagline']),
            'location' => (string) ($branding['location'] ?? $defaults['location']),
            'care_email' => (string) ($branding['care_email'] ?? $defaults['care_email']),
            'phone' => $branding['phone'] ?? null,
            'header_logo_path' => $headerLogoPath,
            'footer_logo_path' => $footerLogoPath,
            'header_logo_url' => $this->publicUrl($headerLogoPath),
            'footer_logo_url' => $this->publicUrl($footerLogoPath),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveSocial(array $settings): array
    {
        $defaults = $this->defaultSocial();
        $stored = is_array($settings['social'] ?? null) ? $settings['social'] : [];

        return array_merge($defaults, array_filter($stored, fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveFooter(array $settings): array
    {
        $defaults = $this->defaultFooter();
        $stored = is_array($settings['footer'] ?? null) ? $settings['footer'] : [];

        return [
            'blurb' => (string) ($stored['blurb'] ?? $defaults['blurb']),
            'copyright_tagline' => (string) ($stored['copyright_tagline'] ?? $defaults['copyright_tagline']),
            'care_links' => $this->normalizeLinks($stored['care_links'] ?? $defaults['care_links']),
            'about_links' => $this->normalizeLinks($stored['about_links'] ?? $defaults['about_links']),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveHomepage(array $settings): array
    {
        $defaults = $this->defaultHomepage();
        $stored = is_array($settings['homepage'] ?? null) ? $settings['homepage'] : [];

        $heroDefaults = $defaults['hero'];
        $heroStored = is_array($stored['hero'] ?? null) ? $stored['hero'] : [];

        $craftDefaults = $defaults['craft_steps'];
        $craftStored = is_array($stored['craft_steps'] ?? null) ? $stored['craft_steps'] : [];

        return [
            'hero' => [
                'eyebrow' => (string) ($heroStored['eyebrow'] ?? $heroDefaults['eyebrow']),
                'headline' => (string) ($heroStored['headline'] ?? $heroDefaults['headline']),
                'body' => (string) ($heroStored['body'] ?? $heroDefaults['body']),
                'primary_cta_label' => (string) ($heroStored['primary_cta_label'] ?? $heroDefaults['primary_cta_label']),
                'primary_cta_url' => (string) ($heroStored['primary_cta_url'] ?? $heroDefaults['primary_cta_url']),
                'secondary_cta_label' => (string) ($heroStored['secondary_cta_label'] ?? $heroDefaults['secondary_cta_label']),
                'secondary_cta_url' => (string) ($heroStored['secondary_cta_url'] ?? $heroDefaults['secondary_cta_url']),
            ],
            'banner_url' => $stored['banner_url'] ?? $settings['homepage_banner_url'] ?? $defaults['banner_url'],
            'categories_eyebrow' => (string) ($stored['categories_eyebrow'] ?? $defaults['categories_eyebrow']),
            'categories_heading' => (string) ($stored['categories_heading'] ?? $defaults['categories_heading']),
            'featured_eyebrow' => (string) ($stored['featured_eyebrow'] ?? $defaults['featured_eyebrow']),
            'featured_heading' => (string) ($stored['featured_heading'] ?? $defaults['featured_heading']),
            'craft_steps' => $this->normalizeCraftSteps($craftStored, $craftDefaults),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveSeo(array $settings): array
    {
        $defaults = $this->defaultSeo();
        $stored = is_array($settings['seo'] ?? null) ? $settings['seo'] : [];

        $ogImagePath = $stored['og_image_path'] ?? $defaults['og_image_path'];

        return [
            'default_description' => (string) ($stored['default_description'] ?? $defaults['default_description']),
            'title_suffix' => (string) ($stored['title_suffix'] ?? $defaults['title_suffix']),
            'og_image_path' => $ogImagePath,
            'og_image_url' => $this->publicUrl($ogImagePath),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveAuth(array $settings): array
    {
        $defaults = $this->defaultAuth();
        $stored = is_array($settings['auth'] ?? null) ? $settings['auth'] : [];

        return [
            'quote' => (string) ($stored['quote'] ?? $defaults['quote']),
            'attribution' => (string) ($stored['attribution'] ?? $defaults['attribution']),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function resolveCurrency(array $settings): string
    {
        $currency = $settings['currency'] ?? $settings['store_currency'] ?? config('store.currency', 'PKR');

        return strtoupper((string) $currency);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBranding(): array
    {
        return [
            'name' => config('store.name', config('app.name', 'Commerce')),
            'tagline' => config('store.tagline', 'Est. Handcrafted Goods'),
            'location' => config('store.location', 'Islamabad, Pakistan'),
            'care_email' => config('store.care_email', config('mail.from.address', 'hello@example.com')),
            'phone' => null,
            'header_logo_path' => null,
            'footer_logo_path' => null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function defaultSocial(): array
    {
        return [
            'instagram' => null,
            'facebook' => null,
            'tiktok' => null,
            'youtube' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultFooter(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultHomepage(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSeo(): array
    {
        return [
            'default_description' => 'Full-grain leather goods, hand-cut and stitched in a small workshop in Islamabad.',
            'title_suffix' => config('app.name', 'Commerce'),
            'og_image_path' => null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function defaultAuth(): array
    {
        return [
            'quote' => "Full-grain leather doesn't wear out — it wears in.",
            'attribution' => 'From the Workshop Floor, Islamabad',
        ];
    }

    /**
     * @param  array<int, mixed>  $links
     * @return array<int, array{label: string, type: string, value: string}>
     */
    protected function normalizeLinks(array $links): array
    {
        return collect($links)
            ->filter(fn ($link) => is_array($link) && ! empty($link['label']) && ! empty($link['value']))
            ->map(fn (array $link): array => [
                'label' => (string) $link['label'],
                'type' => (string) ($link['type'] ?? 'page'),
                'value' => (string) $link['value'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $stored
     * @param  array<int, array<string, string>>  $defaults
     * @return array<int, array{title: string, heading: string, body: string}>
     */
    protected function normalizeCraftSteps(array $stored, array $defaults): array
    {
        $steps = [];

        for ($index = 0; $index < 3; $index++) {
            $default = $defaults[$index] ?? ['title' => '', 'heading' => '', 'body' => ''];
            $step = is_array($stored[$index] ?? null) ? $stored[$index] : [];

            $steps[] = [
                'title' => (string) ($step['title'] ?? $default['title']),
                'heading' => (string) ($step['heading'] ?? $default['heading']),
                'body' => (string) ($step['body'] ?? $default['body']),
            ];
        }

        return $steps;
    }

    protected function publicUrl(?string $path): ?string
    {
        return MediaUrl::absolute($path);
    }
}
