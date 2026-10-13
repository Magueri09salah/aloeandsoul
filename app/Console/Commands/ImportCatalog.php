<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductSheet;
use App\Services\Media\ImageProcessor;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Fills the catalogue from database/assets/products/<slug>/: the product
 * information (details.md, translated from the official Forever pages), the
 * Moroccan retail price and reference, and the photographs.
 *
 * By default only empty fields are filled and products that already have
 * images keep them, so the owner's own edits are never overwritten.
 * Products are never published by this command.
 */
class ImportCatalog extends Command
{
    protected $signature = 'catalog:import
        {--overwrite : Also replace text, prices and references that are already filled in (removes sale prices)}
        {--replace-images : Remove the images a product already has and attach the bundled ones}
        {--dry-run : Show what would change without changing anything}';

    protected $description = 'Fill products with the bundled official information, photos and Moroccan prices';

    /** details.md section => product column */
    private const TEXT = [
        'summary' => 'summary',
        'description' => 'description',
        'benefits' => 'benefits',
        'how to use' => 'usage',
        'ingredients' => 'ingredients',
        'nutrition' => 'nutrition',
        'seo title' => 'seo_title',
        'seo description' => 'seo_description',
    ];

    public function handle(ImageProcessor $processor): int
    {
        $root = database_path('assets/products');
        $folders = glob("{$root}/*/details.md") ?: [];
        if ($folders === []) {
            $this->error("No product information found in {$root}.");

            return self::FAILURE;
        }

        $currency = settings()->currency();
        if ($currency !== 'MAD') {
            $this->warn("The shop currency is {$currency}, not MAD: prices are left unchanged.");
        }

        $updated = 0;
        foreach ($folders as $file) {
            $slug = basename(dirname($file));
            $sheet = ProductSheet::fromFile($file);

            $product = Product::query()->with(['variants', 'images'])->where('slug', $slug)->first();
            if ($product === null) {
                $this->warn("  skip   {$slug}: no product with this web address");

                continue;
            }

            $changes = $this->apply($product, $sheet, $currency, dirname($file), $processor);
            if ($changes === []) {
                $this->line("  same   {$slug}");

                continue;
            }

            $updated++;
            $this->info("  update {$slug}: ".implode(', ', $changes));
            if ($sheet->meta('price') === null && $sheet->meta('price_note') !== null) {
                $this->warn("         no price: {$sheet->meta('price_note')}");
            }
        }

        $this->newLine();
        $this->info($this->option('dry-run')
            ? 'Dry run: nothing was changed.'
            : "{$updated} product(s) updated. Review them in the admin; products stay unpublished until you publish them.");

        return self::SUCCESS;
    }

    /** @return list<string> what changed (or would change) */
    private function apply(Product $product, ProductSheet $sheet, string $currency, string $dir, ImageProcessor $processor): array
    {
        $overwrite = (bool) $this->option('overwrite');
        $changes = [];

        $attributes = [];
        foreach (self::TEXT as $section => $column) {
            $value = $sheet->section($section);
            if ($value !== null && $value !== $product->getAttribute($column) && ($overwrite || blank($product->getAttribute($column)))) {
                $attributes[$column] = $value;
            }
        }
        if ($attributes !== []) {
            $changes[] = 'text ('.implode(', ', array_keys($attributes)).')';
        }

        $specifications = $this->specifications($product, $sheet->meta('size'), $overwrite);
        if ($specifications !== null) {
            $attributes['specifications'] = $specifications;
            $changes[] = 'size';
        }

        $variantUpdates = $this->variantUpdates($product, $sheet, $currency, $overwrite);
        $variantFields = array_merge([], ...array_map('array_keys', array_values($variantUpdates)));
        foreach (['price_minor' => 'price', 'sale_price_minor' => 'sale price removed', 'sku' => 'reference'] as $field => $label) {
            if (in_array($field, $variantFields, true)) {
                $changes[] = $label;
            }
        }

        $images = $sheet->images();
        $attachImages = $images !== [] && ($product->images->isEmpty() || $this->option('replace-images'));
        if ($attachImages) {
            $changes[] = count($images).' image(s)';
        }

        if ($changes === [] || $this->option('dry-run')) {
            return $changes;
        }

        DB::transaction(function () use ($product, $attributes, $variantUpdates, $attachImages, $images, $dir, $processor) {
            if ($attributes !== []) {
                $product->update($attributes);
            }

            foreach ($variantUpdates as $id => $update) {
                ProductVariant::query()->whereKey($id)->update($update);
            }

            if ($attachImages) {
                foreach ($product->images as $old) {
                    $processor->delete($old->path, $old->widths);
                    $old->delete();
                }

                foreach ($images as $position => $image) {
                    // The first image is the product tile: pad it to the 4:5 tile format.
                    $transform = $position === 0 ? fn (ImageInterface $img) => $this->toTileFormat($img) : null;
                    $stored = $processor->storeFromPath("{$dir}/{$image['file']}", "products/{$product->id}", $transform);

                    ProductImage::query()->create([
                        ...$stored,
                        'product_id' => $product->id,
                        'alt' => $image['alt'],
                        'sort_order' => $position,
                    ]);
                }
            }
        });

        return $changes;
    }

    /**
     * The product's details table with a "Size" row, or null when unchanged.
     *
     * @return list<array{label: string, value: string}>|null
     */
    private function specifications(Product $product, ?string $size, bool $overwrite): ?array
    {
        if ($size === null) {
            return null;
        }

        $rows = array_values($product->specifications ?? []);
        foreach ($rows as $i => $row) {
            if (strcasecmp($row['label'], 'Size') === 0) {
                if (! $overwrite || $row['value'] === $size) {
                    return null;
                }
                $rows[$i]['value'] = $size;

                return $rows;
            }
        }

        return [['label' => 'Size', 'value' => $size], ...$rows];
    }

    /**
     * Variant id => columns to update (price, reference).
     *
     * @return array<int, array<string, int|string|null>>
     */
    private function variantUpdates(Product $product, ProductSheet $sheet, string $currency, bool $overwrite): array
    {
        $references = $sheet->variants();
        $price = $currency === 'MAD' ? Money::parse($sheet->meta('price'), $currency) : null;

        $updates = [];
        foreach ($product->variants as $variant) {
            if ($references !== []) {
                $reference = null;
                foreach ($references as $name => $ref) {
                    if (strcasecmp($name, (string) $variant->name) === 0) {
                        $reference = $ref;
                    }
                }
                if ($reference === null) {
                    continue;
                }
            } elseif ($product->variants->count() === 1) {
                $reference = $sheet->meta('reference');
            } else {
                continue;
            }

            $update = [];
            if ($price !== null && $variant->price_minor !== $price && ($overwrite || $variant->price_minor === null)) {
                $update['price_minor'] = $price;
            }
            if ($overwrite && $variant->sale_price_minor !== null) {
                $update['sale_price_minor'] = null;
            }
            if ($reference !== null && $variant->sku !== $reference && ($overwrite || blank($variant->sku))) {
                $taken = ProductVariant::query()->where('sku', $reference)->whereKeyNot($variant->id)->exists();
                if ($taken) {
                    $this->warn("         reference {$reference} is already used by another product");
                } else {
                    $update['sku'] = $reference;
                }
            }

            if ($update !== []) {
                $updates[$variant->id] = $update;
            }
        }

        return $updates;
    }

    /**
     * Packshots on a transparent background are centred on a transparent 4:5
     * canvas with a margin, so the whole product shows in the 4:5 tiles.
     */
    private function toTileFormat(ImageInterface $image): ImageInterface
    {
        $size = max($image->width(), $image->height());
        $canvasWidth = (int) round($size * 1.12);
        $canvasHeight = (int) round($canvasWidth * 1.25);

        return $image->resizeCanvas($canvasWidth, $canvasHeight, 'transparent', 'center');
    }
}
