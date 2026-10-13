# SEO

## How pages are rendered

Inertia sends the first page as HTML. Because shared hosting cannot keep a Node.js SSR process running, **all SEO-critical tags are rendered by Blade** (`resources/views/app.blade.php`) from an `App\Services\Seo\Seo` object each controller builds:

- `<title>` and `<meta name="description">`
- `<meta name="robots">`
- `<link rel="canonical">`
- Open Graph (`og:title`, `og:description`, `og:type`, `og:url`, `og:image`, `og:site_name`) and `twitter:card`
- JSON-LD (`<script type="application/ld+json">`, CSP nonce)

Crawlers and link previews therefore never depend on JavaScript. Google renders the React content. If the hosting plan can run Node, enabling SSR (`INERTIA_SSR_ENABLED=true`, `php artisan inertia:start-ssr`) also server-renders the body; this was verified to work with the built `bootstrap/ssr/ssr.js`.

On client-side navigation, the storefront layout updates title, description, robots and canonical through Inertia `<Head>` (the Blade tags carry `data-inertia` so they are replaced, not duplicated).

## Indexation rules

| Page | Indexed | Canonical |
|---|---|---|
| `/`, `/our-story`, `/faq`, `/contact`, policy pages | Yes | Self |
| `/shop`, `/shop/{category}` (no filters) | Yes | Self; page 2+ canonical to itself (`?page=n`) |
| `/shop…?q=…`, any filter or non-default sort | **noindex,follow** | Unfiltered listing |
| `/products/{slug}` (published) | Yes | Self |
| Draft product | 404 | — |
| Archived (discontinued) product | **410 Gone**, noindex, links to its category | — |
| Out-of-stock product | Yes (200), `availability: OutOfStock` | Self |
| Cart, checkout, confirmation, track-order | noindex + `X-Robots-Tag` | — |
| `/admin/*` | noindex,nofollow + `X-Robots-Tag` | — |

Tests: `tests/Feature/Storefront/CatalogTest.php`, `tests/e2e/storefront.spec.ts` (metadata).

## Structured data (`App\Services\Seo\StructuredData`)

- **Organization** (home): name, URL, logo, email, telephone, country, `sameAs` from configured social links.
- **WebSite** (home).
- **BreadcrumbList**: shop, category and product pages, story.
- **Product**: name, description (plain text), images, SKU (single-variant), category, URL, and an `Offer` (one variant) or `AggregateOffer` (several) with real price, currency, availability and condition.

Never emitted: ratings, reviews, `aggregateRating`, brand (unknown), shipping details or return policy (not yet confirmed by the client) — nothing is fabricated. A product with no priced variant gets no Product schema. ✓T `shows a published product with structured data and no invented ratings`.

## Sitemap and robots

- `/sitemap.xml`: home, shop, story, FAQ, contact, active categories, published products (with `lastmod`), published policy pages. Cached for one hour and flushed automatically whenever a product, category or page is saved or deleted.
- `/robots.txt`: in production disallows `/admin`, `/cart`, `/checkout`, `/track-order` and search URLs, and points to the sitemap. **Outside production it disallows everything**, so a staging copy can never be indexed.

## Migration from the old site

Permanent (301) redirects keep the old Zyro URLs' value:

| Old | New |
|---|---|
| `/my-wellness-journey` | `/our-story#wellness` |
| `/busnises-journey` | `/our-story#business` |
| `/my-freedom-journey` | `/our-story#freedom` |
| `/blog-list` | `/our-story` |
| `/page` (store) | `/shop` |

After launch: submit the sitemap in Google Search Console and request indexing of the home page.

## Content guidance for the client

- One unique title and description per product (admin → product → Search engines). Defaults: product name and summary.
- Category descriptions are editable; describe the range factually. **No health claims** (treats, cures, prevents) in any copy or metadata.
- Every image has editable alternative text (default: product name). Describe what is visible.
- Product URLs come from the name; changing a published product's web address breaks old links.

## Performance

- Each page is its own JavaScript chunk; storefront visitors never download admin code (the admin shell is lazy-loaded).
- Fonts are self-hosted (`@fontsource`, `font-display: swap`); no third-party requests.
- Images: re-encoded WebP at 480/960/1600 px with `srcset`/`sizes` and intrinsic `width`/`height` (no layout shift); `loading="lazy"` below the fold, `fetchpriority="high"` on the hero and main product image.
- Queries: eager loading everywhere (strict mode reports N+1 in development); indexes on `(status, category_id, sort_order)`, `(status, is_featured)`, order `(status, created_at)` and more (see DATABASE.md).
- Navigation and sitemap are cached and invalidated on change. Prices and stock are never cached: checkout always re-reads them.
