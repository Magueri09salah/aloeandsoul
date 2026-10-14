<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\PostStatus;
use App\Enums\ProductStatus;
use App\Models\BlogPost;
use App\Models\Collaborator;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Services\Media\ImageProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * DEVELOPMENT AND TESTING ONLY. Fills the catalogue with placeholder prices,
 * stock and descriptions and publishes everything so the storefront can be
 * built and tested. None of these values are real. Refuses to run in
 * production.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder contains placeholder prices and must never run in production.');
        }

        $this->call(DatabaseSeeder::class);

        $prices = [29000, 34500, 41000, 18500, 26000, 52000, 22500, 31000];
        Product::query()->with('variants')->orderBy('id')->get()->each(function (Product $product, int $i) use ($prices) {
            foreach ($product->variants as $j => $variant) {
                $variant->update([
                    'price_minor' => $prices[($i + $j) % count($prices)],
                    'sale_price_minor' => $i % 7 === 3 ? $prices[($i + $j) % count($prices)] - 4000 : null,
                    'stock' => $i % 9 === 5 ? 0 : 12 + ($i * 3) % 20,
                    'sku' => 'DEMO-'.str_pad((string) ($product->id * 10 + $j), 4, '0', STR_PAD_LEFT),
                ]);
            }

            // A plain placeholder (category tint + brand mark), never a fake
            // packshot, run through the real upload pipeline so demo products
            // meet the same publishing rules as real ones.
            if ($product->images()->doesntExist()) {
                $product->images()->create([
                    ...$this->placeholderImage($product),
                    'alt' => "{$product->name} (placeholder image)",
                    'alt_fr' => $product->translated('name', 'fr').' (image provisoire)',
                ]);
            }

            $product->forceFill([
                'summary' => 'Demo summary. Replace with the official product information.',
                'description' => "**Demo description.** This text is a placeholder for development.\n\nReplace it with the official product information before publishing.",
                'summary_fr' => 'Résumé de démonstration. À remplacer par les informations officielles du produit.',
                'description_fr' => "**Description de démonstration.** Ce texte est provisoire.\n\nRemplacez-le par les informations officielles du produit avant de publier.",
                'specifications' => [['label' => 'Size', 'value' => 'To be confirmed', 'label_fr' => 'Contenance', 'value_fr' => 'À confirmer']],
                'status' => ProductStatus::Published,
                'published_at' => now()->subDays($i),
                'is_featured' => $i % 5 === 0,
                'is_bestseller' => $i % 4 === 1,
            ])->save();
        });

        $zones = [
            ['Marrakech', null, 2000, 50000, '1–2 days', '1 à 2 jours'],
            ['Casablanca', null, 3000, 50000, '2–3 days', '2 à 3 jours'],
            ['Other cities', 'Autres villes', 4000, null, '3–5 days', '3 à 5 jours'],
        ];
        foreach ($zones as $i => [$name, $nameFr, $fee, $free, $estimate, $estimateFr]) {
            ShippingZone::query()->firstOrCreate(['name' => $name], [
                'name_fr' => $nameFr,
                'fee_minor' => $fee,
                'free_over_minor' => $free,
                'estimate' => $estimate,
                'estimate_fr' => $estimateFr,
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $posts = ['Demo post: a slower morning' => 'Article de démonstration : un matin plus lent', 'Demo post: notes from Essaouira' => 'Article de démonstration : notes d’Essaouira'];
        foreach (array_keys($posts) as $i => $title) {
            BlogPost::query()->firstOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'excerpt' => 'Placeholder post for development. Replace or delete it in Admin → Blog.',
                'body' => "This is a **demo post** used while building the site.\n\nWrite real posts in Admin → Blog and delete this one.\n\n## A subheading\n\n- A list item\n- Another item",
                'title_fr' => $posts[$title],
                'excerpt_fr' => 'Article provisoire pour le développement. À remplacer ou supprimer dans Admin → Blog.',
                'body_fr' => "Ceci est un **article de démonstration** utilisé pendant la création du site.\n\nÉcrivez de vrais articles dans Admin → Blog et supprimez celui-ci.\n\n## Un intertitre\n\n- Un élément de liste\n- Un autre élément",
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(2 + $i * 5),
            ]);
        }

        foreach (['Demo Collaborator One', 'Demo Studio', 'Demo Wellness Coach'] as $i => $name) {
            Collaborator::query()->firstOrCreate(['name' => $name], [
                'description' => 'Placeholder, replace in Admin → Collaborators',
                'description_fr' => 'Exemple, à remplacer dans Admin → Collaborators',
                'image_style' => 'photo',
                'sort_order' => $i,
                'is_visible' => true,
            ]);
        }

        Coupon::query()->firstOrCreate(['code' => 'DEMO10'], [
            'description' => 'Demo code, 10% off',
            'type' => CouponType::Percent,
            'value' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{path: string, widths: list<int>, width: int, height: int}
     */
    private function placeholderImage(Product $product): array
    {
        $product->loadMissing('category');
        $manager = new ImageManager(new Driver);
        $mark = $manager->read(public_path('brand/logo-mark-192.png'))->scale(height: 220);
        $canvas = $manager->create(960, 1200)->fill(ltrim($product->category->tint, '#'))->place($mark, 'center');

        $tmp = tempnam(sys_get_temp_dir(), 'demo').'.png';
        $canvas->toPng()->save($tmp);

        try {
            return app(ImageProcessor::class)->store(new UploadedFile($tmp, 'placeholder.png', 'image/png', null, true), "products/{$product->id}");
        } finally {
            @unlink($tmp);
        }
    }
}
