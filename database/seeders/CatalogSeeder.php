<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOptionType;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Support\SkuGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $apparel = Category::query()->updateOrCreate(
            ['slug' => 'apparel'],
            ['name' => 'Apparel', 'position' => 1, 'is_active' => true],
        );

        $accessories = Category::query()->updateOrCreate(
            ['slug' => 'accessories'],
            ['name' => 'Accessories', 'position' => 2, 'is_active' => true],
        );

        $bestsellers = Collection::query()->updateOrCreate(
            ['slug' => 'bestsellers'],
            [
                'name' => 'Bestsellers',
                'type' => 'manual',
                'is_featured' => true,
            ],
        );

        $simpleProducts = [
            ['title' => 'Denim Jacket', 'category' => $apparel, 'price' => 450000, 'stock' => 25],
            ['title' => 'Running Sneakers', 'category' => $apparel, 'price' => 320000, 'stock' => 40],
            ['title' => 'Leather Belt', 'category' => $accessories, 'price' => 85000, 'stock' => 60],
            ['title' => 'Canvas Tote Bag', 'category' => $accessories, 'price' => 120000, 'stock' => 35],
            ['title' => 'Baseball Cap', 'category' => $accessories, 'price' => 65000, 'stock' => 50],
            ['title' => 'Wool Scarf', 'category' => $accessories, 'price' => 95000, 'stock' => 30],
            ['title' => 'Sunglasses', 'category' => $accessories, 'price' => 180000, 'stock' => 20],
        ];

        $position = 0;

        foreach ($simpleProducts as $data) {
            $product = $this->createProduct($data['title'], $data['price'], $data['stock']);
            $product->categories()->syncWithoutDetaching([$data['category']->id]);
            $bestsellers->products()->syncWithoutDetaching([
                $product->id => ['position' => $position++],
            ]);
        }

        $tee = $this->createSizedTee($apparel);
        $bestsellers->products()->syncWithoutDetaching([
            $tee->id => ['position' => $position],
        ]);
    }

    protected function createProduct(string $title, int $priceCents, int $stock): Product
    {
        $slug = Str::slug($title);

        $product = Product::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $title,
                'description' => "{$title} — quality item for everyday wear.",
                'status' => ProductStatus::Active,
                'brand' => 'Commerce',
                'published_at' => now(),
            ],
        );

        ProductVariant::query()->updateOrCreate(
            ['product_id' => $product->id, 'is_default' => true],
            [
                'sku' => SkuGenerator::generate(Str::upper(Str::substr($slug, 0, 3))),
                'price_cents' => $priceCents,
                'compare_at_cents' => (int) ($priceCents * 1.15),
                'currency' => 'PKR',
                'stock_quantity' => $stock,
                'is_active' => true,
            ],
        );

        ProductImage::query()->firstOrCreate(
            ['product_id' => $product->id, 'path' => 'products/placeholder.jpg'],
            [
                'alt' => $title,
                'position' => 0,
                'is_primary' => true,
            ],
        );

        return $product;
    }

    protected function createSizedTee(Category $category): Product
    {
        $product = Product::query()->updateOrCreate(
            ['slug' => 'classic-cotton-tee'],
            [
                'title' => 'Classic Cotton Tee',
                'description' => 'Soft cotton t-shirt available in S, M, and L.',
                'status' => ProductStatus::Active,
                'brand' => 'Commerce',
                'published_at' => now(),
            ],
        );

        $product->categories()->syncWithoutDetaching([$category->id]);

        $sizeType = ProductOptionType::query()->firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Size'],
            ['position' => 0],
        );

        $sizes = [
            'S' => ['price' => 150000, 'stock' => 30],
            'M' => ['price' => 150000, 'stock' => 45],
            'L' => ['price' => 160000, 'stock' => 25],
        ];

        $isFirst = true;

        foreach ($sizes as $size => $config) {
            $optionValue = ProductOptionValue::query()->firstOrCreate(
                ['option_type_id' => $sizeType->id, 'value' => $size],
                ['position' => ord($size) - ord('S')],
            );

            $variant = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->id, 'sku' => 'TEE-'.$size.'-001'],
                [
                    'price_cents' => $config['price'],
                    'compare_at_cents' => 180000,
                    'currency' => 'PKR',
                    'stock_quantity' => $config['stock'],
                    'is_default' => $isFirst,
                    'is_active' => true,
                ],
            );

            $variant->optionValues()->sync([$optionValue->id]);
            $isFirst = false;
        }

        ProductImage::query()->firstOrCreate(
            ['product_id' => $product->id, 'path' => 'products/tee-placeholder.jpg'],
            [
                'alt' => 'Classic Cotton Tee',
                'position' => 0,
                'is_primary' => true,
            ],
        );

        return $product;
    }
}
