<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Safe for every environment, including production, and safe to re-run:
 * creates only missing records and never overwrites edits made in the admin.
 * It creates no users and no prices.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
