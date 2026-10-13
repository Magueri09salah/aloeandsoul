<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Bare order rows for admin/list tests. Tests that need consistent items and
 * stock create orders through the real PlaceOrder service instead.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'reference' => 'AS-'.now()->format('ym').'-'.strtoupper(Str::random(5)),
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => fake()->name(),
            'customer_phone' => '+2126'.fake()->numerify('########'),
            'city' => 'Marrakech',
            'address' => fake()->streetAddress(),
            'shipping_zone_name' => 'Marrakech',
            'currency' => 'MAD',
            'subtotal_minor' => 25000,
            'discount_minor' => 0,
            'shipping_minor' => 3000,
            'total_minor' => 28000,
            'payment_method' => 'cod',
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::PendingConfirmation,
            'privacy_consent_at' => now(),
        ];
    }

    /** Order has guarded totals; the factory fills them directly. */
    public function newModel(array $attributes = []): Order
    {
        return (new Order)->forceFill($attributes);
    }
}
