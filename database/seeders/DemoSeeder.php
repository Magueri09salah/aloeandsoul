<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\ProductStatus;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Services\Media\ImageProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
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
                $product->images()->create([...$this->placeholderImage($product), 'alt' => "{$product->name} (placeholder image)"]);
            }

            $product->forceFill([
                'summary' => 'Demo summary. Replace with the official product information.',
                'description' => "**Demo description.** This text is a placeholder for development.\n\nReplace it with the official product information before publishing.",
                'specifications' => [['label' => 'Size', 'value' => 'To be confirmed']],
                'status' => ProductStatus::Published,
                'published_at' => now()->subDays($i),
                'is_featured' => $i % 5 === 0,
                'is_bestseller' => $i % 4 === 1,
            ])->save();
        });

        foreach ([['Marrakech', 2000, 50000, '1–2 days'], ['Casablanca', 3000, 50000, '2–3 days'], ['Other cities', 4000, null, '3–5 days']] as $i => [$name, $fee, $free, $estimate]) {
            ShippingZone::query()->firstOrCreate(['name' => $name], [
                'fee_minor' => $fee,
                'free_over_minor' => $free,
                'estimate' => $estimate,
                'is_active' => true,
                'sort_order' => $i,
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
