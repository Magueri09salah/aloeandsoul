<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * schema.org JSON-LD built only from real data. No ratings, reviews,
 * shipping promises or return policies are emitted unless they exist.
 */
class StructuredData
{
    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $contact = settings()->group('contact');
        $social = array_values(array_filter(settings()->group('social')));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => settings()->get('store.name'),
            'url' => url('/'),
            'logo' => asset('brand/logo-512.png'),
            'email' => $contact['email'] ?: null,
            'telephone' => $contact['phone'] ?: null,
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => settings()->country(),
            ],
            'sameAs' => $social ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => settings()->get('store.name'),
            'url' => url('/'),
        ];
    }

    /** @return array<string, mixed> */
    public static function blogPosting(BlogPost $post, ?string $imageUrl): array
    {
        $publisher = [
            '@type' => 'Organization',
            'name' => settings()->get('store.name'),
            'logo' => ['@type' => 'ImageObject', 'url' => asset('brand/logo-512.png')],
        ];

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => Str::limit($post->title, 110, ''),
            'description' => $post->excerpt,
            'image' => $imageUrl,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => settings()->get('store.name'), 'url' => url('/')],
            'publisher' => $publisher,
            'mainEntityOfPage' => route('blog.show', $post),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * Requires variants, images and category loaded. Returns null when the
     * product has no priced variant (no Offer can be stated truthfully).
     *
     * @return array<string, mixed>|null
     */
    public static function product(Product $product): ?array
    {
        $currency = settings()->currency();
        $variants = $product->variants->filter(fn (ProductVariant $v) => $v->is_active && $v->effectivePriceMinor() !== null);
        if ($variants->isEmpty()) {
            return null;
        }

        $url = route('products.show', $product);
        $availability = fn (ProductVariant $v) => (! $v->track_stock || $v->stock > 0)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        if ($variants->count() === 1) {
            $variant = $variants->first();
            $offers = [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => $currency,
                'price' => Money::toDecimalString($variant->effectivePriceMinor(), $currency),
                'availability' => $availability($variant),
                'itemCondition' => 'https://schema.org/NewCondition',
            ];
        } else {
            $prices = $variants->map(fn (ProductVariant $v) => $v->effectivePriceMinor());
            $offers = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => $currency,
                'lowPrice' => Money::toDecimalString((int) $prices->min(), $currency),
                'highPrice' => Money::toDecimalString((int) $prices->max(), $currency),
                'offerCount' => $variants->count(),
                'availability' => $variants->contains(fn ($v) => $availability($v) === 'https://schema.org/InStock')
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ];
        }

        $single = $variants->count() === 1 ? $variants->first() : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => Str::limit(strip_tags(Str::markdown((string) ($product->description ?: $product->summary))), 500),
            'image' => $product->images->map(fn ($img) => $img->urlFor(max($img->widths)))->values()->all() ?: null,
            'sku' => $single?->sku,
            'category' => $product->category?->name,
            'url' => $url,
            'offers' => $offers,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
