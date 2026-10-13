<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Media\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Attaches the official product photographs stored in database/assets/products
 * (see manifest.json there) to the matching products. Run once after the first
 * deployment. Products are not published by this command.
 *
 * A file named "<slug>--<anything>.webp" is added as an extra image of <slug>
 * (e.g. c9--chocolate for the chocolate C9 pack).
 */
class ImportProductImages extends Command
{
    protected $signature = 'catalog:import-images
        {--replace : Remove the images a product already has before importing}
        {--dry-run : Show what would happen without changing anything}';

    protected $description = 'Attach the bundled official product photographs to their products';

    public function handle(ImageProcessor $processor): int
    {
        $dir = database_path('assets/products');
        $manifest = json_decode((string) @file_get_contents("{$dir}/manifest.json"), true);
        if (! is_array($manifest) || ! isset($manifest['products']) || ! is_array($manifest['products'])) {
            $this->error("No manifest found in {$dir}.");

            return self::FAILURE;
        }

        // Group files by product: "c9" and "c9--chocolate" both belong to c9.
        $byProduct = [];
        foreach ($manifest['products'] as $key => $entry) {
            $slug = explode('--', (string) $key)[0];
            $byProduct[$slug][] = ['key' => (string) $key, 'file' => "{$dir}/{$entry['file']}"];
        }

        $attached = 0;
        foreach ($byProduct as $slug => $files) {
            $product = Product::query()->with('images')->where('slug', $slug)->first();
            if ($product === null) {
                $this->warn("  skip  {$slug}: no product with this web address");

                continue;
            }
            if ($product->images->isNotEmpty() && ! $this->option('replace')) {
                $this->line("  keep  {$slug}: already has images (use --replace to overwrite)");

                continue;
            }

            $this->info("  add   {$slug}: ".count($files).' image(s)');
            if ($this->option('dry-run')) {
                continue;
            }

            DB::transaction(function () use ($product, $files, $processor) {
                if ($this->option('replace')) {
                    foreach ($product->images as $old) {
                        $processor->delete($old->path, $old->widths);
                        $old->delete();
                    }
                }

                foreach ($files as $position => $f) {
                    $suffix = str_contains($f['key'], '--') ? ucfirst(str_replace('-', ' ', explode('--', $f['key'])[1])) : null;
                    $stored = $processor->storeFromPath($f['file'], "products/{$product->id}", fn (ImageInterface $img) => $this->toProductFormat($img));

                    ProductImage::query()->create([
                        ...$stored,
                        'product_id' => $product->id,
                        'alt' => $suffix ? "{$product->name}, {$suffix}" : $product->name,
                        'sort_order' => $position,
                    ]);
                }
            });
            $attached++;
        }

        foreach ((array) ($manifest['missing'] ?? []) as $slug => $reason) {
            $this->warn("  none  {$slug}: {$reason}");
        }

        $this->newLine();
        $this->info($this->option('dry-run') ? 'Dry run: nothing was changed.' : "Images attached to {$attached} product(s). Products stay unpublished until you publish them.");

        return self::SUCCESS;
    }

    /**
     * Square packshots on a transparent background are centred on a
     * transparent 4:5 canvas with a margin, so the whole product shows in the
     * 4:5 product tiles over the category colour.
     */
    private function toProductFormat(ImageInterface $image): ImageInterface
    {
        $size = max($image->width(), $image->height());
        $canvasWidth = (int) round($size * 1.12);
        $canvasHeight = (int) round($canvasWidth * 1.25);

        return $image->resizeCanvas($canvasWidth, $canvasHeight, 'transparent', 'center');
    }
}
