<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone' => '+2126'.fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'city' => 'Marrakech',
            'first_order_at' => now(),
            'last_order_at' => now(),
        ];
    }
}
