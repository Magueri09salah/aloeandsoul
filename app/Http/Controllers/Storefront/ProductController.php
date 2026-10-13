<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ProductPresenter;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Services\Cart\CartService;
use App\Services\Catalog\ProductQuery;
use App\Services\Seo\Seo;
use App\Services\Seo\StructuredData;
use Illuminate\Support\Str;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProductController extends Controller
{
    public function show(Product $product, ProductQuery $query, CartService $cart): Response|SymfonyResponse
    {
        $product->load(['category', 'images', 'variants', 'primaryImage']);

        // Drafts don't exist publicly; archived products are gone for good.
        abort_if($product->status === ProductStatus::Draft || ! $product->category->is_active, 404);

        if ($product->status === ProductStatus::Archived) {
            return $this->render('product/unavailable', [
                'name' => $product->name,
                'category' => ['name' => $product->category->name, 'slug' => $product->category->slug],
            ], Seo::page($product->name, null, index: false))->toResponse(request())->setStatusCode(410);
        }

        $detail = ProductPresenter::detail($product, $cart->maxPerLine());
        // A published product whose variants all lost their price is not sellable.
        abort_if($detail['variants'] === [], 404);

        $crumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Shop', 'url' => route('shop')],
            ['name' => $product->category->name, 'url' => route('shop.category', $product->category)],
            ['name' => $product->name, 'url' => route('products.show', $product)],
        ];

        $related = $query->published()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('is_featured')->orderBy('sort_order')
            ->limit(4)->get();

        $zones = ShippingZone::query()->active()->get();

        $seo = new Seo(
            title: $product->seo_title ?: $product->name,
            description: $product->seo_description ?: ($product->summary ?: Str::limit(strip_tags((string) $detail['description_html']), 155)),
            canonical: route('products.show', $product),
            image: $product->images->first()?->urlFor(max($product->images->first()->widths)),
            type: 'product',
            jsonLd: array_values(array_filter([StructuredData::product($product), StructuredData::breadcrumbs($crumbs)])),
        );

        return $this->render('product/show', [
            'product' => $detail,
            'related' => $related->map(fn (Product $p) => ProductPresenter::card($p)),
            'breadcrumbs' => $crumbs,
            'delivery' => [
                'cod_instructions' => settings()->get('checkout.cod_instructions'),
                'info' => settings()->get('checkout.delivery_info'),
                'min_fee_minor' => $zones->min('fee_minor'),
                'zones' => $zones->map(fn (ShippingZone $z) => [
                    'name' => $z->name,
                    'fee_minor' => $z->fee_minor,
                    'free_over_minor' => $z->free_over_minor,
                    'estimate' => $z->estimate,
                ]),
            ],
        ], $seo);
    }
}
