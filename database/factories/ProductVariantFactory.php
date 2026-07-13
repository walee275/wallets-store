<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\SkuGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => SkuGenerator::generate('VAR'),
            'price_cents' => fake()->numberBetween(50000, 500000),
            'currency' => 'PKR',
            'stock_quantity' => fake()->numberBetween(5, 100),
            'is_default' => true,
            'is_active' => true,
        ];
    }
}
