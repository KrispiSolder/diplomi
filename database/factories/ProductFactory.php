<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => Str::slug(fake()->unique()->words(2, true).'-'.fake()->unique()->numerify('###')),
            'article' => fake()->unique()->numerify('ART-###'),
            'price' => fake()->numberBetween(100, 5000),
            'description' => fake()->sentence(),
            'stock_status_id' => fn () => StockStatus::idFor('in_stock'),
            'quantity' => 5,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            if ($product->categories()->count() === 0) {
                $category = Category::factory()->create();
                Product::syncCategoryPivot($product, [$category->id]);
            }
            if ($product->images()->count() === 0) {
                $product->images()->create([
                    'image_url' => '/placeholder.svg?height=270&width=270',
                    'order' => 0,
                ]);
            }
        });
    }

    public function withStockStatus(string $label): static
    {
        return $this->state(function () use ($label) {
            $id = StockStatus::idFor($label);

            return ['stock_status_id' => $id];
        });
    }
}
