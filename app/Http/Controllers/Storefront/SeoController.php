<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Services\Catalog\ProductQuery;
use App\Support\Locales;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'seo.sitemap';

    /** Pages customers should not land on from a search engine. */
    private const PRIVATE_PATHS = ['/cart', '/checkout', '/track-order'];

    public function sitemap(ProductQuery $products): Response
    {
        $xml = Cache::remember(self::SITEMAP_CACHE_KEY, now()->addHour(), function () use ($products) {
            /** @var list<array{0: string, 1: mixed, 2: Carbon|null}> $pages route name, parameters, last modified */
            $pages = [
                ['home', [], null],
                ['shop', [], null],
                ['story', [], null],
                ['blog', [], null],
                ['faq', [], null],
                ['contact', [], null],
            ];

            foreach (Category::query()->active()->get() as $category) {
                $pages[] = ['shop.category', $category, $category->updated_at];
            }

            foreach ($products->published()->without(['primaryImage', 'activeVariants', 'category'])->get(['products.id', 'products.slug', 'products.updated_at']) as $product) {
                $pages[] = ['products.show', $product, $product->updated_at];
            }

            foreach (BlogPost::query()->live()->latest('published_at')->get(['id', 'slug', 'updated_at']) as $post) {
                $pages[] = ['blog.show', $post, $post->updated_at];
            }

            foreach (Page::query()->where('is_published', true)->get(['slug', 'updated_at']) as $page) {
                $pages[] = ['page', ['slug' => $page->slug], $page->updated_at];
            }

            $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

            foreach ($pages as [$route, $parameters, $lastmod]) {
                // One entry per language, each listing every language version.
                $urls = [];
                foreach (Locales::codes() as $locale) {
                    $urls[$locale] = localized_route($route, $parameters, $locale);
                }
                $alternates = '';
                foreach ($urls as $locale => $url) {
                    $alternates .= '<xhtml:link rel="alternate" hreflang="'.$locale.'" href="'.e($url).'"/>';
                }
                $alternates .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.e($urls[Locales::DEFAULT]).'"/>';

                foreach ($urls as $url) {
                    $body .= '  <url><loc>'.e($url).'</loc>'.($lastmod ? '<lastmod>'.$lastmod->toAtomString().'</lastmod>' : '').$alternates."</url>\n";
                }
            }

            return $body.'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $private = [];
        foreach (Locales::codes() as $locale) {
            foreach (self::PRIVATE_PATHS as $path) {
                $private[] = 'Disallow: '.Locales::path($path, $locale);
            }
        }

        $lines = app()->isProduction()
            ? [
                'User-agent: *',
                'Disallow: /admin',
                ...$private,
                'Disallow: /*?*q=',
                '',
                'Sitemap: '.url('sitemap.xml'),
            ]
            // Never let a staging or local copy be indexed.
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
