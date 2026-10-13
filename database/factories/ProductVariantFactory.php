<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => null,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'price_minor' => 25000,
            'sale_price_minor' => null,
            'stock' => 10,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
