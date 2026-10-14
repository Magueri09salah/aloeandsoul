<?php

namespace App\Http\Presenters;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ProductPresenter
{
    /** Product page sections, in display order: column => heading (translated when shown). */
    public const SECTIONS = [
        'description' => 'Description',
        'benefits' => 'Benefits',
        'usage' => 'How to use',
        'ingredients' => 'Ingredients',
        'nutrition' => 'Nutrition information',
    ];

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
            'name' => $product->translated('name'),
            'slug' => $product->slug,
            'summary' => $product->translated('summary'),
            'category' => [
                'name' => $product->category->translated('name'),
                'slug' => $product->category->slug,
                'tint' => $product->category->tint,
            ],
            'image' => $image ? ImagePresenter::present($image, (string) $product->translated('name')) : null,
            'price_minor' => $cheapest?->effectivePriceMinor(),
            'regular_price_minor' => $cheapest?->price_minor,
            'price_varies' => $prices->count() > 1,
            'on_sale' => $variants->contains(fn (ProductVariant $v) => $v->isOnSale()),
            'in_stock' => $purchasable->isNotEmpty(),
            'is_bestseller' => $product->is_bestseller,
            'variant_count' => $variants->count(),
            'option_label' => $product->translated('option_label'),
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

        $sections = [];
        foreach (self::SECTIONS as $field => $title) {
            $text = $product->translated($field);
            if (is_string($text) && trim($text) !== '') {
                $sections[] = ['id' => $field, 'title' => __($title), 'html' => self::markdown($text)];
            }
        }

        return [
            ...self::card($product),
            'description_html' => $product->translated('description') ? self::markdown((string) $product->translated('description')) : null,
            'sections' => $sections,
            'specifications' => self::specifications($product),
            'size' => self::size($product),
            'images' => $product->images->map(fn (ProductImage $img) => ImagePresenter::present($img, (string) $product->translated('name')))->values()->all(),
            'variants' => $variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'name' => $v->translated('name'),
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

    /**
     * Details table rows in the current language. A row is {label, value}
     * with optional label_fr / value_fr.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function specifications(Product $product, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $rows = [];
        foreach ($product->specifications ?? [] as $row) {
            if (! is_array($row) || blank($row['label'] ?? null) || blank($row['value'] ?? null)) {
                continue;
            }
            $label = $row["label_{$locale}"] ?? null;
            $value = $row["value_{$locale}"] ?? null;
            $rows[] = [
                'label' => (string) (filled($label) ? $label : $row['label']),
                'value' => (string) (filled($value) ? $value : $row['value']),
            ];
        }

        return $rows;
    }

    /** The "Size" details row (e.g. "60 tablets"), shown under the price. */
    public static function size(Product $product, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        foreach ($product->specifications ?? [] as $row) {
            if (is_array($row) && mb_strtolower(trim((string) ($row['label'] ?? ''))) === 'size' && filled($row['value'] ?? null)) {
                $value = $row["value_{$locale}"] ?? null;

                return (string) (filled($value) ? $value : $row['value']);
            }
        }

        return null;
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
