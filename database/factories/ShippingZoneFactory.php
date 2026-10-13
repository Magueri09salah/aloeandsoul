<?php

namespace Database\Factories;

use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'fee_minor' => 3000,
            'free_over_minor' => null,
            'estimate' => '2–3 days',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
