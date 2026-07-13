<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\SkuGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::Active,
            'brand' => fake()->company(),
            'published_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->variants()->create([
                'sku' => SkuGenerator::generate('PRD'),
                'price_cents' => fake()->numberBetween(50000, 500000),
                'currency' => 'PKR',
                'stock_quantity' => fake()->numberBetween(5, 100),
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }
}
