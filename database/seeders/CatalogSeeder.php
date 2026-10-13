<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The four categories and the client's product list. Products are created as
 * drafts with names only: no price, stock, description or image is invented.
 * The admin completes and publishes each one.
 */
class CatalogSeeder extends Seeder
{
    /** @var array<string, array{name: string, tagline: string, description: string, tint: string, motif: string, products: list<string|array{name: string, option: string, variants: list<string>}>}> */
    public const CATALOG = [
        'sport-fitness' => [
            'name' => 'Sport & Fitness',
            'tagline' => 'For training days and active routines.',
            'description' => 'Lotions, protein and supplements for people who train and move. Each product page lists what the product is and how it is supplied.',
            'tint' => '#EDE3D1',
            'motif' => 'horizon',
            'products' => [
                'Heat Lotion',
                'Arginine',
                ['name' => 'C9', 'option' => 'Flavour', 'variants' => ['Vanilla', 'Chocolate']],
                'Vanilla Protein Ultra',
                'Chocolate Protein Ultra',
            ],
        ],
        'beauty' => [
            'name' => 'Beauty',
            'tagline' => 'Skincare for your daily routine.',
            'description' => 'Masks, creams and skincare to build a simple, considered routine.',
            'tint' => '#EFDFD6',
            'motif' => 'circle',
            'products' => [
                'Marine Collagen',
                'Aloe Cellulose Bio Mask',
                'Eye Contour Cream',
                'Intense Hydrating Care',
                'Three-Step Routine',
                'Firming Complex',
            ],
        ],
        'hygiene' => [
            'name' => 'Hygiene',
            'tagline' => 'Everyday care for body and hair.',
            'description' => 'Deodorant, toothpaste, shampoo, conditioner and body care for every day.',
            'tint' => '#DFE6E4',
            'motif' => 'wave',
            'products' => [
                'Deodorant',
                'Toothpaste',
                'Shampoo',
                'After-Shampoo / Conditioner',
                'Aloe Body Wash',
                'Aloe Body Lotion',
            ],
        ],
        'nutrition-supplements' => [
            'name' => 'Nutrition & Supplements',
            'tagline' => 'Aloe drinks and food supplements.',
            'description' => 'Aloe vera drinks and food supplements. Food supplements should not replace a varied, balanced diet and a healthy lifestyle.',
            'tint' => '#E1E7DA',
            'motif' => 'leaf',
            'products' => [
                'Aloe Vera Gel',
                'Aloe Mango',
                'Aloe Peach',
                'Aloe Berry',
                'Bee Propolis',
                'Arctic Sea',
                'Forever Vision',
                'Forever Daily',
                'Forever Calcium',
            ],
        ],
    ];

    public function run(): void
    {
        $categoryOrder = 0;
        foreach (self::CATALOG as $slug => $data) {
            $category = Category::query()->firstOrCreate(['slug' => $slug], [
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'description' => $data['description'],
                'tint' => $data['tint'],
                'motif' => $data['motif'],
                'sort_order' => $categoryOrder++,
                'is_active' => true,
            ]);

            foreach ($data['products'] as $position => $entry) {
                $name = is_array($entry) ? $entry['name'] : $entry;
                $productSlug = Str::slug($name);

                if (Product::query()->where('slug', $productSlug)->exists()) {
                    continue;
                }

                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => $productSlug,
                    'option_label' => is_array($entry) ? $entry['option'] : null,
                    'status' => ProductStatus::Draft,
                    'sort_order' => $position,
                ]);

                $variants = is_array($entry) ? $entry['variants'] : [null];
                foreach ($variants as $i => $variantName) {
                    $product->variants()->create([
                        'name' => $variantName,
                        'price_minor' => null,
                        'stock' => 0,
                        'track_stock' => true,
                        'is_active' => true,
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }
}
