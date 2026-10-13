<?php

namespace Database\Factories;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Correct-Horse-9'),
            'role' => AdminRole::Staff,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => AdminRole::Owner]);
    }

    public function manager(): static
    {
        return $this->state(['role' => AdminRole::Manager]);
    }

    public function staff(): static
    {
        return $this->state(['role' => AdminRole::Staff]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
