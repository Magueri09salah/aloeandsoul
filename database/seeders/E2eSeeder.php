<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fixtures for the Playwright end-to-end suite (tests/e2e). Local/testing
 * only: demo catalogue, two purpose-built products and a test owner account
 * whose credentials live in tests/e2e/fixtures.ts.
 */
class E2eSeeder extends Seeder
{
    public const OWNER_EMAIL = 'e2e-owner@example.test';

    public const OWNER_PASSWORD = 'E2e-Owner-Pass-2026';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('E2eSeeder must never run in production.');
        }

        $this->call(DemoSeeder::class);

        $category = Category::query()->where('slug', 'hygiene')->firstOrFail();

        $last = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'E2E Last Unit Soap',
            'slug' => 'e2e-last-unit-soap',
            'summary' => 'Test product with a single unit in stock.',
            'description' => 'Test product.',
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);
        $last->variants()->create(['sku' => 'E2E-LAST', 'price_minor' => 10000, 'stock' => 1, 'is_active' => true]);

        $out = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'E2E Sold Out Balm',
            'slug' => 'e2e-sold-out-balm',
            'summary' => 'Test product with no stock.',
            'description' => 'Test product.',
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);
        $out->variants()->create(['sku' => 'E2E-OUT', 'price_minor' => 10000, 'stock' => 0, 'is_active' => true]);

        $owner = User::query()->create([
            'name' => 'E2E Owner',
            'email' => self::OWNER_EMAIL,
            'password' => self::OWNER_PASSWORD,
            'role' => AdminRole::Owner,
            'is_active' => true,
        ]);
        $owner->forceFill(['email_verified_at' => now()])->save();
    }
}
