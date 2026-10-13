<?php

namespace App\Http\Presenters;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ProductPresenter
{
    /**
     * Compact shape for product grids. Requires `category`, `primaryImage`
     * and `activeVariants` (or `variants`) loaded.
     *
     * @return array<string, mixed>
     */
    public static function card(Product $product): array
    {
        $variants = self::sellable($product);
        $cheapest = $variants->sortBy(fn (ProductVariant $v) => $v->effectivePriceMinor())->first();
        $prices = $variants->map(fn (ProductVariant $v) => $v->effectivePriceMinor())->unique();
        $image = $product->relationLoaded('primaryImage') ? $product->primaryImage : $product->images->first();

        $purchasable = $variants->filter(fn (ProductVariant $v) => ! $v->track_stock || $v->stock > 0);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'summary' => $product->summary,
            'category' => [
                'name' => $product->category->name,
                'slug' => $product->category->slug,
                'tint' => $product->category->tint,
            ],
            'image' => $image ? ImagePresenter::present($image, $product->name) : null,
            'price_minor' => $cheapest?->effectivePriceMinor(),
            'regular_price_minor' => $cheapest?->price_minor,
            'price_varies' => $prices->count() > 1,
            'on_sale' => $variants->contains(fn (ProductVariant $v) => $v->isOnSale()),
            'in_stock' => $purchasable->isNotEmpty(),
            'is_bestseller' => $product->is_bestseller,
            'variant_count' => $variants->count(),
            'option_label' => $product->option_label,
            // Single-option products can be added straight from the grid.
            'quick_add_variant_id' => $variants->count() === 1 && $purchasable->count() === 1 ? $purchasable->first()->id : null,
        ];
    }

    /**
     * Full product page shape. Requires `category`, `images`, `variants`.
     *
     * @return array<string, mixed>
     */
    public static function detail(Product $product, int $maxPerLine): array
    {
        $variants = self::sellable($product);

        return [
            ...self::card($product),
            'description_html' => $product->description ? self::markdown($product->description) : null,
            'specifications' => array_values(array_filter(
                $product->specifications ?? [],
                fn ($row) => is_array($row) && filled($row['label'] ?? null) && filled($row['value'] ?? null),
            )),
            'images' => $product->images->map(fn (ProductImage $img) => ImagePresenter::present($img, $product->name))->values()->all(),
            'variants' => $variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'sku' => $v->sku,
                'price_minor' => $v->effectivePriceMinor(),
                'regular_price_minor' => $v->price_minor,
                'on_sale' => $v->isOnSale(),
                'in_stock' => ! $v->track_stock || $v->stock > 0,
                'max_quantity' => $v->purchasableQuantity($maxPerLine),
                'low_stock' => $v->track_stock && $v->stock > 0 && $v->stock <= 3,
            ])->values()->all(),
        ];
    }

    /**
     * Markdown → HTML with raw HTML stripped and unsafe links removed, so the
     * result is safe to inject into the page.
     */
    public static function markdown(string $text): string
    {
        return Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);
    }

    /** @return Collection<int, ProductVariant> */
    private static function sellable(Product $product): Collection
    {
        $variants = $product->relationLoaded('activeVariants') ? $product->activeVariants : $product->variants;

        return $variants
            ->filter(fn (ProductVariant $v) => $v->is_active && $v->price_minor !== null && $v->price_minor > 0)
            ->values();
    }
}
