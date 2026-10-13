<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::Draft,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => ProductStatus::Published, 'published_at' => now()]);
    }

    public function archived(): static
    {
        return $this->state(['status' => ProductStatus::Archived]);
    }

    /** A published product with a single priced, stocked variant. */
    public function sellable(int $priceMinor = 25000, int $stock = 10): static
    {
        return $this->published()->has(
            ProductVariant::factory()->state(['price_minor' => $priceMinor, 'stock' => $stock]),
            'variants',
        );
    }
}
