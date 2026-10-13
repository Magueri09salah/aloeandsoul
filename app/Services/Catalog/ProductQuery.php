<?php

namespace App\Services\Catalog;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Storefront product listing: search, filters and sorting over published
 * products. All inputs arrive pre-validated (see ShopRequest).
 */
class ProductQuery
{
    public const SORTS = ['featured', 'newest', 'price_asc', 'price_desc', 'name'];

    public const PER_PAGE = 12;

    /** SQL for a variant's effective (sale-aware) price. */
    private const EFFECTIVE_PRICE = 'CASE WHEN sale_price_minor IS NOT NULL AND sale_price_minor < price_minor THEN sale_price_minor ELSE price_minor END';

    /**
     * @param  array{q?: string|null, availability?: string|null, on_sale?: bool|null, min_price?: int|null, max_price?: int|null, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters, ?Category $category = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('products.status', ProductStatus::Published)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true))
            ->whereExists(fn (QueryBuilder $q) => $this->sellableVariants($q))
            ->with(['category', 'primaryImage', 'activeVariants'])
            ->addSelect([
                'products.*',
                'min_price' => fn (QueryBuilder $q) => $q
                    ->selectRaw('MIN('.self::EFFECTIVE_PRICE.')')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_active', true)
                    ->whereNotNull('price_minor'),
            ]);

        if ($category !== null) {
            $query->where('category_id', $category->id);
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.addcslashes((string) $filters['q'], '%_\\').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('products.name', 'like', $term)
                    ->orWhere('products.summary', 'like', $term)
                    ->orWhereHas('activeVariants', fn (Builder $v) => $v->where('sku', 'like', $term));
            });
        }

        if (($filters['availability'] ?? null) === 'in_stock') {
            $query->whereExists(fn (QueryBuilder $q) => $this->sellableVariants($q)
                ->where(fn (QueryBuilder $s) => $s->where('track_stock', false)->orWhere('stock', '>', 0)));
        }

        if (! empty($filters['on_sale'])) {
            $query->whereExists(fn (QueryBuilder $q) => $this->sellableVariants($q)
                ->whereNotNull('sale_price_minor')
                ->whereColumn('sale_price_minor', '<', 'price_minor'));
        }

        $min = $filters['min_price'] ?? null;
        $max = $filters['max_price'] ?? null;
        if ($min !== null || $max !== null) {
            $query->whereExists(function (QueryBuilder $q) use ($min, $max) {
                $this->sellableVariants($q);
                if ($min !== null) {
                    $q->whereRaw(self::EFFECTIVE_PRICE.' >= ?', [$min]);
                }
                if ($max !== null) {
                    $q->whereRaw(self::EFFECTIVE_PRICE.' <= ?', [$max]);
                }
            });
        }

        match ($filters['sort'] ?? 'featured') {
            'newest' => $query->orderByDesc('published_at')->orderByDesc('products.id'),
            'price_asc' => $query->orderBy('min_price')->orderBy('products.name'),
            'price_desc' => $query->orderByDesc('min_price')->orderBy('products.name'),
            'name' => $query->orderBy('products.name'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('products.id'),
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Published products for the home page / related products.
     *
     * @return Builder<Product>
     */
    public function published(): Builder
    {
        return Product::query()
            ->published()
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true))
            ->whereExists(fn (QueryBuilder $q) => $this->sellableVariants($q))
            ->with(['category', 'primaryImage', 'activeVariants']);
    }

    private function sellableVariants(QueryBuilder $q): QueryBuilder
    {
        return $q->selectRaw('1')
            ->from('product_variants')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('product_variants.is_active', true)
            ->whereNotNull('product_variants.price_minor')
            ->where('product_variants.price_minor', '>', 0);
    }
}
