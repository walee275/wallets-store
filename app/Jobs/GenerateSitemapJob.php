<?php

namespace App\Jobs;

use App\Enums\ProductStatus;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\URL;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url as SitemapUrl;

class GenerateSitemapJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $sitemap = Sitemap::create();

        $sitemap->add(
            SitemapUrl::create(URL::route('home'))
                ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        Product::query()
            ->where('status', ProductStatus::Active)
            ->orderBy('id')
            ->each(function (Product $product) use ($sitemap) {
                $sitemap->add(
                    SitemapUrl::create(URL::to('/products/'.$product->slug))
                        ->setLastModificationDate($product->updated_at)
                        ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8)
                );
            });

        Page::query()
            ->where('is_published', true)
            ->orderBy('id')
            ->each(function (Page $page) use ($sitemap) {
                $sitemap->add(
                    SitemapUrl::create(URL::to('/pages/'.$page->slug))
                        ->setLastModificationDate($page->updated_at)
                        ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.6)
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
