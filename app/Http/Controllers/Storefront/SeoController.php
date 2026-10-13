<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Services\Catalog\ProductQuery;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'seo.sitemap';

    public function sitemap(ProductQuery $products): Response
    {
        $xml = Cache::remember(self::SITEMAP_CACHE_KEY, now()->addHour(), function () use ($products) {
            $urls = [
                ['loc' => url('/'), 'lastmod' => null],
                ['loc' => route('shop'), 'lastmod' => null],
                ['loc' => route('story'), 'lastmod' => null],
                ['loc' => route('faq'), 'lastmod' => null],
                ['loc' => route('contact'), 'lastmod' => null],
            ];

            foreach (Category::query()->active()->get() as $category) {
                $urls[] = ['loc' => route('shop.category', $category), 'lastmod' => $category->updated_at];
            }

            foreach ($products->published()->without(['primaryImage', 'activeVariants', 'category'])->get(['products.id', 'products.slug', 'products.updated_at']) as $product) {
                $urls[] = ['loc' => route('products.show', $product), 'lastmod' => $product->updated_at];
            }

            foreach (Page::query()->where('is_published', true)->get(['slug', 'updated_at']) as $page) {
                $urls[] = ['loc' => url($page->slug), 'lastmod' => $page->updated_at];
            }

            $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($urls as $u) {
                $body .= '  <url><loc>'.e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.$u['lastmod']->toAtomString().'</lastmod>' : '')."</url>\n";
            }

            return $body.'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /cart',
                'Disallow: /checkout',
                'Disallow: /track-order',
                'Disallow: /*?*q=',
                '',
                'Sitemap: '.url('sitemap.xml'),
            ]
            // Never let a staging or local copy be indexed.
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
