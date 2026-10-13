<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ProductPresenter;
use App\Http\Requests\Storefront\ShopRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductQuery;
use App\Services\Seo\Seo;
use App\Services\Seo\StructuredData;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(ShopRequest $request, ProductQuery $query): Response
    {
        return $this->listing($request, $query, null);
    }

    public function category(ShopRequest $request, ProductQuery $query, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, $query, $category);
    }

    private function listing(ShopRequest $request, ProductQuery $query, ?Category $category): Response
    {
        $filters = $request->filters();
        $page = $request->page();
        $products = $query->paginate($filters, $category);

        $base = $category ? route('shop.category', $category) : route('shop');
        // Filtered/searched views are not indexed; plain paginated pages are.
        $canonical = $page > 1 ? "{$base}?page={$page}" : $base;
        $title = $category ? ($category->seo_title ?: $category->name) : 'Shop';
        $description = $category
            ? ($category->seo_description ?: ($category->tagline ?: "Shop {$category->name} at Aloe & Soul. Pay in cash on delivery."))
            : 'Browse wellness, beauty, hygiene, fitness and nutrition products from Aloe & Soul. Pay in cash on delivery.';

        $crumbs = [['name' => 'Home', 'url' => url('/')], ['name' => 'Shop', 'url' => route('shop')]];
        if ($category) {
            $crumbs[] = ['name' => $category->name, 'url' => route('shop.category', $category)];
        }

        $seo = new Seo(
            title: $page > 1 ? "{$title} — page {$page}" : $title,
            description: $description,
            canonical: $canonical,
            index: ! $request->hasRefinements(),
            jsonLd: [StructuredData::breadcrumbs($crumbs)],
        );

        return $this->render('shop/index', [
            'category' => $category ? [
                'name' => $category->name,
                'slug' => $category->slug,
                'tagline' => $category->tagline,
                'description' => $category->description,
                'tint' => $category->tint,
                'motif' => $category->motif,
            ] : null,
            'categories' => Category::query()->active()
                ->withCount(['publishedProducts as product_count'])
                ->get(['id', 'name', 'slug', 'tint'])
                ->map(fn (Category $c) => ['name' => $c->name, 'slug' => $c->slug, 'tint' => $c->tint, 'product_count' => $c->product_count]),
            'products' => [
                'data' => collect($products->items())->map(fn (Product $p) => ProductPresenter::card($p)),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'links' => [
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl(),
                    'pages' => collect(range(1, max(1, $products->lastPage())))
                        ->map(fn (int $n) => ['page' => $n, 'url' => $products->url($n)]),
                ],
            ],
            'filters' => [
                'q' => $filters['q'],
                'availability' => $filters['availability'],
                'on_sale' => $filters['on_sale'],
                'min_price' => $request->query('min_price') && $filters['min_price'] !== null ? (string) $request->query('min_price') : null,
                'max_price' => $request->query('max_price') && $filters['max_price'] !== null ? (string) $request->query('max_price') : null,
                'sort' => $filters['sort'],
            ],
            'breadcrumbs' => $crumbs,
        ], $seo);
    }
}
